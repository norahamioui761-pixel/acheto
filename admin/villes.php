<?php
require_once __DIR__ . '/../includes/lang.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin-textes.php';

$admin = exiger_admin();
$pdo   = db();

$retour = 'admin/villes.php';

// Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_verifier()) {
        flash('danger', ta_brut('err_csrf'));
        redirect($retour);
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'ajouter') {
        $nom_fr = trim($_POST['nom_fr'] ?? '');
        $nom_ar = trim($_POST['nom_ar'] ?? '');

        if (mb_strlen($nom_fr) < 2 || mb_strlen($nom_fr) > 80 || mb_strlen($nom_ar) < 2 || mb_strlen($nom_ar) > 80) {
            flash('danger', ta_brut('city_names_len'));
        } else {
            try {
                $ins = $pdo->prepare('INSERT INTO villes (nom_fr, nom_ar) VALUES (:fr, :ar)');
                $ins->execute(['fr' => $nom_fr, 'ar' => $nom_ar]);
                flash('success', ta_brut('city_added'));
            } catch (PDOException $ex) {
                if ($ex->getCode() === '23000') {
                    flash('danger', ta_brut('city_exists'));
                } else {
                    error_log('Acheto - ville : ' . $ex->getMessage());
                    flash('danger', ta_brut('add_error'));
                }
            }
        }

    } elseif ($action === 'supprimer') {
        $id = (int) ($_POST['id'] ?? 0);

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM annonces WHERE ville_id = :id');
        $stmt->execute(['id' => $id]);

        if ((int) $stmt->fetchColumn() > 0) {
            flash('danger', ta_brut('city_has_ads'));
        } else {
            $del = $pdo->prepare('DELETE FROM villes WHERE id = :id');
            $del->execute(['id' => $id]);
            flash('success', ta_brut('city_deleted'));
        }
    }

    redirect($retour);
}

$villes = $pdo->query(
    'SELECT v.id, v.nom_fr, v.nom_ar,
            (SELECT COUNT(*) FROM annonces a WHERE a.ville_id = v.id) AS nb_annonces,
            (SELECT COUNT(*) FROM utilisateurs u WHERE u.ville_id = v.id) AS nb_utilisateurs
     FROM villes v
     ORDER BY v.nom_fr'
)->fetchAll();

$page_title = ta_brut('cities_title');
require_once __DIR__ . '/../includes/header.php';

$style_carte = 'background:var(--ac-surface); border:1px solid var(--ac-border); border-radius:var(--ac-radius); box-shadow:var(--ac-shadow);';
?>

<main class="container py-5">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h3 fw-bold mb-0"><?= ta('cities_title') ?></h1>
        <a class="btn btn-outline-secondary btn-sm" href="<?= BASE_URL ?>admin/index.php"><?= ta('dashboard') ?></a>
    </div>

    <?= flash_afficher() ?>

    <div class="p-4 mb-4" style="<?= $style_carte ?>">
        <h2 class="h5 fw-bold mb-3"><?= ta('add_city') ?></h2>
        <form method="post" action="" class="row g-2">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="ajouter">

            <div class="col-12 col-md-5">
                <input type="text" name="nom_fr" class="form-control" placeholder="Nom en français" maxlength="80" dir="ltr" required>
            </div>
            <div class="col-12 col-md-5">
                <input type="text" name="nom_ar" class="form-control" placeholder="الاسم بالعربية" maxlength="80" dir="rtl" required>
            </div>
            <div class="col-12 col-md-2">
                <button type="submit" class="btn btn-ac-orange w-100"><?= ta('add') ?></button>
            </div>
        </form>
    </div>

    <div class="p-3" style="<?= $style_carte ?>">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th><?= ta('th_french') ?></th>
                        <th><?= ta('th_arabic') ?></th>
                        <th class="text-center"><?= ta('th_ads') ?></th>
                        <th class="text-center"><?= ta('th_users') ?></th>
                        <th><?= ta('actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($villes as $v): ?>
                        <tr>
                            <td dir="ltr"><?= e($v['nom_fr']) ?></td>
                            <td dir="rtl"><?= e($v['nom_ar']) ?></td>
                            <td class="text-center"><?= (int) $v['nb_annonces'] ?></td>
                            <td class="text-center"><?= (int) $v['nb_utilisateurs'] ?></td>
                            <td>
                                <form method="post" action="" class="m-0"
                                      onsubmit="return confirm(<?= e(json_encode(ta_brut('confirm_del_city'), JSON_UNESCAPED_UNICODE)) ?>);">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="supprimer">
                                    <input type="hidden" name="id" value="<?= (int) $v['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><?= ta('delete') ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>