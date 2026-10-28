<?php
require_once __DIR__ . '/../includes/lang.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/uploads.php';
require_once __DIR__ . '/../includes/admin-textes.php';

$admin = exiger_admin();
$pdo   = db();

$q = is_string($_GET['q'] ?? null) ? mb_substr(trim($_GET['q']), 0, 100) : '';
$retour = 'admin/utilisateurs.php' . ($q !== '' ? '?q=' . rawurlencode($q) : '');

// Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_verifier()) {
        flash('danger', ta_brut('err_csrf'));
        redirect($retour);
    }

    $action = $_POST['action'] ?? '';
    $id     = (int) ($_POST['id'] ?? 0);

    // Seuls les membres peuvent etre modifies ou supprimes ici
    $stmt = $pdo->prepare("SELECT id, actif FROM utilisateurs WHERE id = :id AND role = 'membre'");
    $stmt->execute(['id' => $id]);
    $cible = $stmt->fetch();

    if (!$cible) {
        flash('danger', ta_brut('user_not_found'));
        redirect($retour);
    }

    if ($action === 'basculer') {
        $maj = $pdo->prepare('UPDATE utilisateurs SET actif = :a WHERE id = :id');
        $maj->execute(['a' => $cible['actif'] ? 0 : 1, 'id' => $id]);
        flash('success', ta_brut($cible['actif'] ? 'account_off' : 'account_on'));

    } elseif ($action === 'supprimer') {
        // On retient les annonces pour supprimer leurs photos ensuite
        $stmt = $pdo->prepare('SELECT id FROM annonces WHERE utilisateur_id = :id');
        $stmt->execute(['id' => $id]);
        $ids_annonces = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $del = $pdo->prepare("DELETE FROM utilisateurs WHERE id = :id AND role = 'membre'");
        $del->execute(['id' => $id]);

        foreach ($ids_annonces as $id_annonce) {
            supprimer_dossier(dossier_uploads((string) (int) $id_annonce));
        }

        flash('success', ta_brut('user_deleted'));
    }

    redirect($retour);
}

// Liste
$params = [];
$where  = '1 = 1';

if ($q !== '') {
    $motif = '%' . addcslashes($q, '%_\\') . '%';
    $where = '(u.nom LIKE :n OR u.email LIKE :e)';
    $params = ['n' => $motif, 'e' => $motif];
}

$stmt = $pdo->prepare(
    "SELECT u.id, u.nom, u.email, u.role, u.actif, u.date_inscription,
            (SELECT COUNT(*) FROM annonces a WHERE a.utilisateur_id = u.id) AS nb_annonces
     FROM utilisateurs u
     WHERE $where
     ORDER BY u.date_inscription DESC
     LIMIT 100"
);
$stmt->execute($params);
$utilisateurs = $stmt->fetchAll();

$page_title = ta_brut('users_title');
require_once __DIR__ . '/../includes/header.php';

$style_carte = 'background:var(--ac-surface); border:1px solid var(--ac-border); border-radius:var(--ac-radius); box-shadow:var(--ac-shadow);';
?>

<main class="container py-5">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h3 fw-bold mb-0"><?= ta('users_title') ?></h1>
        <a class="btn btn-outline-secondary btn-sm" href="<?= BASE_URL ?>admin/index.php"><?= ta('dashboard') ?></a>
    </div>

    <form method="get" action="" class="d-flex gap-2 mb-3">
        <input type="search" name="q" class="form-control" value="<?= e($q) ?>" placeholder="<?= ta('search_ph') ?>" maxlength="100">
        <button type="submit" class="btn btn-ac-blue"><i class="bi bi-search"></i></button>
    </form>

    <?= flash_afficher() ?>

    <div class="p-3" style="<?= $style_carte ?>">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th><?= ta('th_name') ?></th>
                        <th><?= ta('th_email') ?></th>
                        <th><?= ta('th_registered') ?></th>
                        <th class="text-center"><?= ta('th_ads') ?></th>
                        <th><?= ta('th_status') ?></th>
                        <th><?= ta('actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($utilisateurs as $u): ?>
                        <tr>
                            <td>
                                <?= e($u['nom']) ?>
                                <?php if ($u['role'] === 'admin'): ?>
                                    <span class="badge text-bg-primary"><?= ta('admin_badge') ?></span>
                                <?php endif; ?>
                            </td>
                            <td dir="ltr"><?= e($u['email']) ?></td>
                            <td dir="ltr"><?= e(date('d/m/Y', strtotime($u['date_inscription']))) ?></td>
                            <td class="text-center"><?= (int) $u['nb_annonces'] ?></td>
                            <td>
                                <?php if ($u['actif']): ?>
                                    <span class="badge text-bg-success"><?= ta('active') ?></span>
                                <?php else: ?>
                                    <span class="badge text-bg-secondary"><?= ta('disabled') ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($u['role'] === 'membre'): ?>
                                    <div class="d-flex flex-wrap gap-1">
                                        <form method="post" action="?q=<?= e(rawurlencode($q)) ?>" class="m-0">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="basculer">
                                            <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">
                                                <?= ta($u['actif'] ? 'deactivate' : 'activate') ?>
                                            </button>
                                        </form>

                                        <form method="post" action="?q=<?= e(rawurlencode($q)) ?>" class="m-0"
                                              onsubmit="return confirm(<?= e(json_encode(ta_brut('confirm_del_user'), JSON_UNESCAPED_UNICODE)) ?>);">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="supprimer">
                                            <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><?= ta('delete') ?></button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted-ac small">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if (!$utilisateurs): ?>
            <p class="text-center text-muted-ac mt-3 mb-0"><?= ta('no_users') ?></p>
        <?php endif; ?>
    </div>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>