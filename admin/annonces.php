<?php
require_once __DIR__ . '/../includes/lang.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/uploads.php';
require_once __DIR__ . '/../includes/cartes.php';
require_once __DIR__ . '/../includes/admin-textes.php';

$admin = exiger_admin();
$pdo   = db();

// Onglets et conditions SQL associees (valeurs fixes, jamais issues de l'utilisateur)
$onglets = [
    'en_attente' => ['tab_pending',  "a.statut = 'en_attente'"],
    'approuvee'  => ['tab_approved', "a.statut = 'approuvee' AND a.date_expiration > NOW()"],
    'refusee'    => ['tab_refused',  "a.statut = 'refusee'"],
    'expiree'    => ['tab_expired',  "(a.statut = 'expiree' OR (a.statut = 'approuvee' AND a.date_expiration <= NOW()))"],
    'toutes'     => ['tab_all',      '1 = 1'],
];

$filtre = $_GET['statut'] ?? ($_POST['statut'] ?? 'en_attente');
if (!is_string($filtre) || !isset($onglets[$filtre])) {
    $filtre = 'en_attente';
}

$retour = 'admin/annonces.php?statut=' . $filtre;

// Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_verifier()) {
        flash('danger', ta_brut('err_csrf'));
        redirect($retour);
    }

    $action = $_POST['action'] ?? '';
    $id     = (int) ($_POST['id'] ?? 0);

    $stmt = $pdo->prepare('SELECT id, a_la_une FROM annonces WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $annonce = $stmt->fetch();

    if (!$annonce) {
        flash('danger', ta_brut('ad_not_found'));
        redirect($retour);
    }

    if ($action === 'approuver') {
        $maj = $pdo->prepare(
            "UPDATE annonces
             SET statut = 'approuvee',
                 date_approbation = NOW(),
                 date_expiration = DATE_ADD(NOW(), INTERVAL 60 DAY),
                 motif_refus = NULL
             WHERE id = :id"
        );
        $maj->execute(['id' => $id]);
        flash('success', ta_brut('ad_approved'));

    } elseif ($action === 'refuser') {
        $motif = trim($_POST['motif'] ?? '');

        if (mb_strlen($motif) < 3 || mb_strlen($motif) > 255) {
            flash('danger', ta_brut('reason_len'));
        } else {
            $maj = $pdo->prepare(
                "UPDATE annonces
                 SET statut = 'refusee',
                     a_la_une = 0,
                     motif_refus = :motif,
                     date_approbation = NULL,
                     date_expiration = NULL
                 WHERE id = :id"
            );
            $maj->execute(['motif' => $motif, 'id' => $id]);
            flash('success', ta_brut('ad_refused'));
        }

    } elseif ($action === 'a_la_une') {
        if ($annonce['a_la_une']) {
            $maj = $pdo->prepare('UPDATE annonces SET a_la_une = 0 WHERE id = :id');
            $maj->execute(['id' => $id]);
            flash('success', ta_brut('ad_unfeatured'));
        } else {
            // Seules les annonces publiques peuvent etre mises a la une
            $maj = $pdo->prepare(
                "UPDATE annonces SET a_la_une = 1
                 WHERE id = :id AND statut = 'approuvee' AND date_expiration > NOW()"
            );
            $maj->execute(['id' => $id]);
            flash(
                $maj->rowCount() ? 'success' : 'danger',
                ta_brut($maj->rowCount() ? 'ad_featured' : 'feature_only')
            );
        }

    } elseif ($action === 'supprimer') {
        $del = $pdo->prepare('DELETE FROM annonces WHERE id = :id');
        $del->execute(['id' => $id]);
        supprimer_dossier(dossier_uploads((string) $id));
        flash('success', ta_brut('ad_deleted'));
    }

    redirect($retour);
}

// Compteurs pour les onglets
$compteurs = [];
foreach ($onglets as $cle => [$cle_texte, $condition]) {
    $compteurs[$cle] = (int) $pdo->query("SELECT COUNT(*) FROM annonces a WHERE $condition")->fetchColumn();
}

// Liste des annonces
$condition = $onglets[$filtre][1];
$annonces = $pdo->query(
    "SELECT a.id, a.titre, a.description, a.prix, a.statut, a.a_la_une, a.motif_refus,
            a.telephone_contact, a.whatsapp, a.date_creation, a.date_expiration,
            c.nom_fr AS cat_fr, c.nom_ar AS cat_ar,
            v.nom_fr AS ville_fr, v.nom_ar AS ville_ar,
            u.nom AS auteur, u.email AS auteur_email
     FROM annonces a
     JOIN categories c ON c.id = a.categorie_id
     JOIN villes v ON v.id = a.ville_id
     JOIN utilisateurs u ON u.id = a.utilisateur_id
     WHERE $condition
     ORDER BY a.date_creation DESC
     LIMIT 50"
)->fetchAll();

// Photos de ces annonces
$images = [];
if ($annonces) {
    $ids = array_map('intval', array_column($annonces, 'id'));
    $marques = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare(
        "SELECT annonce_id, chemin FROM images_annonces
         WHERE annonce_id IN ($marques)
         ORDER BY est_principale DESC, ordre ASC"
    );
    $stmt->execute($ids);
    foreach ($stmt->fetchAll() as $img) {
        $images[(int) $img['annonce_id']][] = $img['chemin'];
    }
}

$page_title = ta_brut('ads_title');
require_once __DIR__ . '/../includes/header.php';

$style_carte = 'background:var(--ac-surface); border:1px solid var(--ac-border); border-radius:var(--ac-radius); box-shadow:var(--ac-shadow);';
?>

<main class="container py-5">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h3 fw-bold mb-0"><?= ta('ads_title') ?></h1>
        <a class="btn btn-outline-secondary btn-sm" href="<?= BASE_URL ?>admin/index.php"><?= ta('dashboard') ?></a>
    </div>

    <ul class="nav nav-pills gap-1 mb-4 flex-wrap">
        <?php foreach ($onglets as $cle => [$cle_texte, $cond]): ?>
            <li class="nav-item">
                <a class="nav-link <?= $filtre === $cle ? 'active' : '' ?>"
                   href="<?= BASE_URL ?>admin/annonces.php?statut=<?= e($cle) ?>">
                    <?= ta($cle_texte) ?> <span class="badge text-bg-light"><?= (int) $compteurs[$cle] ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <?= flash_afficher() ?>

    <?php if (!$annonces): ?>
        <div class="p-4 text-center text-muted-ac" style="<?= $style_carte ?>"><?= ta('none_in_tab') ?></div>
    <?php endif; ?>

    <?php foreach ($annonces as $a):
        $statut = $a['statut'];
        if ($statut === 'approuvee' && $a['date_expiration'] !== null && strtotime($a['date_expiration']) <= time()) {
            $statut = 'expiree';
        }
        $badges = [
            'en_attente' => ['warning',   'badge_pending'],
            'approuvee'  => ['success',   'badge_approved'],
            'refusee'    => ['danger',    'badge_refused'],
            'expiree'    => ['secondary', 'badge_expired'],
        ];
        [$classe_badge, $cle_badge] = $badges[$statut] ?? ['secondary', 'badge_pending'];
        $photos = $images[(int) $a['id']] ?? [];
    ?>
        <div class="p-3 mb-4" style="<?= $style_carte ?>">
            <div class="row g-3">

                <div class="col-12 col-md-4">
                    <?php if ($photos): ?>
                        <img src="<?= e(BASE_URL . 'uploads/annonces/' . $photos[0]) ?>" alt=""
                             style="width:100%; aspect-ratio:4/3; object-fit:cover; border-radius:10px;">
                        <?php if (count($photos) > 1): ?>
                            <div class="d-flex flex-wrap gap-1 mt-2">
                                <?php foreach (array_slice($photos, 1) as $p): ?>
                                    <a href="<?= e(BASE_URL . 'uploads/annonces/' . $p) ?>" target="_blank" rel="noopener">
                                        <img src="<?= e(BASE_URL . 'uploads/annonces/' . $p) ?>" alt=""
                                             style="width:56px; height:56px; object-fit:cover; border-radius:6px; border:1px solid var(--ac-border);">
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <div class="col-12 col-md-8">
                    <div class="d-flex flex-wrap justify-content-between gap-2">
                        <h2 class="h5 fw-bold mb-1"><?= e($a['titre']) ?></h2>
                        <div class="d-flex gap-1 align-self-start">
                            <?php if ($a['a_la_une']): ?>
                                <span class="badge text-bg-warning"><i class="bi bi-star-fill"></i> <?= ta('featured') ?></span>
                            <?php endif; ?>
                            <span class="badge text-bg-<?= $classe_badge ?>"><?= ta($cle_badge) ?></span>
                        </div>
                    </div>

                    <p class="ac-price mb-1"><?= e(formater_prix($a['prix'], $lang)) ?></p>

                    <p class="text-muted-ac small mb-1">
                        <?= e($lang === 'ar' ? $a['ville_ar'] : $a['ville_fr']) ?> ·
                        <?= e($lang === 'ar' ? $a['cat_ar'] : $a['cat_fr']) ?> ·
                        <?= ta('sent_on') ?> <span dir="ltr"><?= e(date('d/m/Y H:i', strtotime($a['date_creation']))) ?></span>
                        <?php if ($a['date_expiration'] !== null && $statut !== 'refusee'): ?>
                            · <?= ta('expires_on') ?> <span dir="ltr"><?= e(date('d/m/Y', strtotime($a['date_expiration']))) ?></span>
                        <?php endif; ?>
                    </p>

                    <p class="small mb-2">
                        <strong><?= e($a['auteur']) ?></strong> (<span dir="ltr"><?= e($a['auteur_email']) ?></span>)
                        · <?= ta('tel') ?> <span dir="ltr"><?= e($a['telephone_contact']) ?></span>
                        <?php if (!empty($a['whatsapp'])): ?> · <?= ta('whatsapp') ?><?php endif; ?>
                    </p>

                    <p class="small" style="white-space: pre-line;"><?= e($a['description']) ?></p>

                    <?php if ($statut === 'refusee' && !empty($a['motif_refus'])): ?>
                        <p class="small text-danger"><?= ta('reason_label') ?> <?= e($a['motif_refus']) ?></p>
                    <?php endif; ?>

                    <div class="d-flex flex-wrap gap-2 align-items-start">

                        <?php if ($statut !== 'approuvee'): ?>
                            <form method="post" action="" class="m-0">
                                <?= csrf_field() ?>
                                <input type="hidden" name="statut" value="<?= e($filtre) ?>">
                                <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                                <input type="hidden" name="action" value="approuver">
                                <button type="submit" class="btn btn-sm btn-success">
                                    <i class="bi bi-check-lg me-1"></i><?= ta('approve') ?>
                                </button>
                            </form>
                        <?php endif; ?>

                        <?php if ($statut === 'approuvee'): ?>
                            <form method="post" action="" class="m-0">
                                <?= csrf_field() ?>
                                <input type="hidden" name="statut" value="<?= e($filtre) ?>">
                                <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                                <input type="hidden" name="action" value="a_la_une">
                                <button type="submit" class="btn btn-sm btn-outline-warning">
                                    <i class="bi bi-star me-1"></i><?= ta($a['a_la_une'] ? 'unfeature' : 'feature') ?>
                                </button>
                            </form>
                        <?php endif; ?>

                        <?php if ($statut !== 'refusee'): ?>
                            <form method="post" action="" class="m-0 d-flex gap-1">
                                <?= csrf_field() ?>
                                <input type="hidden" name="statut" value="<?= e($filtre) ?>">
                                <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                                <input type="hidden" name="action" value="refuser">
                                <input type="text" name="motif" class="form-control form-control-sm"
                                       placeholder="<?= ta('reason_ph') ?>" maxlength="255" required>
                                <button type="submit" class="btn btn-sm btn-warning text-nowrap">
                                    <i class="bi bi-x-lg me-1"></i><?= ta('refuse') ?>
                                </button>
                            </form>
                        <?php endif; ?>

                        <form method="post" action="" class="m-0"
                              onsubmit="return confirm(<?= e(json_encode(ta_brut('confirm_del_ad'), JSON_UNESCAPED_UNICODE)) ?>);">
                            <?= csrf_field() ?>
                            <input type="hidden" name="statut" value="<?= e($filtre) ?>">
                            <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                            <input type="hidden" name="action" value="supprimer">
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash me-1"></i><?= ta('delete') ?>
                            </button>
                        </form>

                    </div>
                </div>

            </div>
        </div>
    <?php endforeach; ?>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>