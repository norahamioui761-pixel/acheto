<?php
require_once __DIR__ . '/../includes/lang.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin-textes.php';

$admin = exiger_admin();
$pdo   = db();

// Icones autorisees (Bootstrap Icons) => cle du libelle traduit
$icones = [
    'bi-tag'         => 'icon_tag',
    'bi-car-front'   => 'icon_car',
    'bi-building'    => 'icon_building',
    'bi-phone'       => 'icon_phone',
    'bi-laptop'      => 'icon_laptop',
    'bi-lamp'        => 'icon_home',
    'bi-handbag'     => 'icon_bag',
    'bi-briefcase'   => 'icon_briefcase',
    'bi-trophy'      => 'icon_trophy',
    'bi-emoji-smile' => 'icon_animals',
    'bi-tools'       => 'icon_tools',
    'bi-book'        => 'icon_book',
    'bi-camera'      => 'icon_camera',
    'bi-controller'  => 'icon_games',
    'bi-bicycle'     => 'icon_bike',
    'bi-music-note'  => 'icon_music',
    'bi-three-dots'  => 'icon_other',
];

$retour = 'admin/categories.php';

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
        $icone  = $_POST['icone'] ?? '';

        if (mb_strlen($nom_fr) < 2 || mb_strlen($nom_fr) > 100 || mb_strlen($nom_ar) < 2 || mb_strlen($nom_ar) > 100) {
            flash('danger', ta_brut('cat_names_len'));
        } elseif (!is_string($icone) || !isset($icones[$icone])) {
            flash('danger', ta_brut('icon_invalid'));
        } else {
            try {
                $ins = $pdo->prepare('INSERT INTO categories (nom_fr, nom_ar, icone) VALUES (:fr, :ar, :ic)');
                $ins->execute(['fr' => $nom_fr, 'ar' => $nom_ar, 'ic' => $icone]);
                flash('success', ta_brut('cat_added'));
            } catch (PDOException $ex) {
                if ($ex->getCode() === '23000') {
                    flash('danger', ta_brut('cat_exists'));
                } else {
                    error_log('Acheto - categorie : ' . $ex->getMessage());
                    flash('danger', ta_brut('add_error'));
                }
            }
        }

    } elseif ($action === 'basculer') {
        $id = (int) ($_POST['id'] ?? 0);
        $maj = $pdo->prepare('UPDATE categories SET actif = 1 - actif WHERE id = :id');
        $maj->execute(['id' => $id]);
        flash('success', ta_brut('cat_updated'));

    } elseif ($action === 'supprimer') {
        $id = (int) ($_POST['id'] ?? 0);

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM annonces WHERE categorie_id = :id');
        $stmt->execute(['id' => $id]);

        if ((int) $stmt->fetchColumn() > 0) {
            flash('danger', ta_brut('cat_has_ads'));
        } else {
            $del = $pdo->prepare('DELETE FROM categories WHERE id = :id');
            $del->execute(['id' => $id]);
            flash('success', ta_brut('cat_deleted'));
        }
    }

    redirect($retour);
}

$categories = $pdo->query(
    'SELECT c.id, c.nom_fr, c.nom_ar, c.icone, c.actif,
            (SELECT COUNT(*) FROM annonces a WHERE a.categorie_id = c.id) AS nb_annonces
     FROM categories c
     ORDER BY c.id'
)->fetchAll();

$page_title = ta_brut('cats_title');
require_once __DIR__ . '/../includes/header.php';

$style_carte = 'background:var(--ac-surface); border:1px solid var(--ac-border); border-radius:var(--ac-radius); box-shadow:var(--ac-shadow);';
?>

<main class="container py-5">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h3 fw-bold mb-0"><?= ta('cats_title') ?></h1>
        <a class="btn btn-outline-secondary btn-sm" href="<?= BASE_URL ?>admin/index.php"><?= ta('dashboard') ?></a>
    </div>

    <?= flash_afficher() ?>

    <div class="p-4 mb-4" style="<?= $style_carte ?>">
        <h2 class="h5 fw-bold mb-3"><?= ta('add_category') ?></h2>
        <form method="post" action="" class="row g-2">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="ajouter">

            <div class="col-12 col-md-4">
                <input type="text" name="nom_fr" class="form-control" placeholder="Nom en français" maxlength="100" dir="ltr" required>
            </div>
            <div class="col-12 col-md-4">
                <input type="text" name="nom_ar" class="form-control" placeholder="الاسم بالعربية" maxlength="100" dir="rtl" required>
            </div>
            <div class="col-8 col-md-2">
                <select name="icone" class="form-select" required>
                    <?php foreach ($icones as $classe => $cle_texte): ?>
                        <option value="<?= e($classe) ?>"><?= ta($cle_texte) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-4 col-md-2">
                <button type="submit" class="btn btn-ac-orange w-100"><?= ta('add') ?></button>
            </div>
        </form>
    </div>

    <div class="p-3" style="<?= $style_carte ?>">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th></th>
                        <th><?= ta('th_french') ?></th>
                        <th><?= ta('th_arabic') ?></th>
                        <th class="text-center"><?= ta('th_ads') ?></th>
                        <th><?= ta('th_status') ?></th>
                        <th><?= ta('actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $c): ?>
                        <tr>
                            <td><i class="bi <?= e($c['icone']) ?> fs-5" style="color:var(--ac-blue);"></i></td>
                            <td dir="ltr"><?= e($c['nom_fr']) ?></td>
                            <td dir="rtl"><?= e($c['nom_ar']) ?></td>
                            <td class="text-center"><?= (int) $c['nb_annonces'] ?></td>
                            <td>
                                <?php if ($c['actif']): ?>
                                    <span class="badge text-bg-success"><?= ta('active_f') ?></span>
                                <?php else: ?>
                                    <span class="badge text-bg-secondary"><?= ta('disabled_f') ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    <form method="post" action="" class="m-0">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="basculer">
                                        <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                                            <?= ta($c['actif'] ? 'deactivate' : 'activate') ?>
                                        </button>
                                    </form>

                                    <form method="post" action="" class="m-0"
                                          onsubmit="return confirm(<?= e(json_encode(ta_brut('confirm_del_cat'), JSON_UNESCAPED_UNICODE)) ?>);">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="supprimer">
                                        <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><?= ta('delete') ?></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>