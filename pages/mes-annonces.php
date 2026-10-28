<?php
require_once __DIR__ . '/../includes/lang.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/uploads.php';

$user = exiger_connexion();
$pdo  = db();

$textes_page = [
    'fr' => [
        'title'       => 'Mes annonces',
        'empty'       => 'Vous n\'avez pas encore publié d\'annonce.',
        'publish'     => 'Publier une annonce',
        'created'     => 'Envoyée le',
        'expires'     => 'Expire le',
        'reason'      => 'Motif du refus :',
        'delete'      => 'Supprimer',
        'confirm_del' => 'Supprimer cette annonce définitivement ?',
        'st_wait'     => 'En attente',
        'st_ok'       => 'Approuvée',
        'st_no'       => 'Refusée',
        'st_exp'      => 'Expirée',
        'err_csrf'    => 'Session expirée. Veuillez réessayer.',
        'ok_del'      => 'Annonce supprimée.',
        'currency'    => 'DH',
    ],
    'ar' => [
        'title'       => 'إعلاناتي',
        'empty'       => 'لم تنشر أي إعلان بعد.',
        'publish'     => 'انشر إعلانا',
        'created'     => 'أرسل في',
        'expires'     => 'ينتهي في',
        'reason'      => 'سبب الرفض:',
        'delete'      => 'حذف',
        'confirm_del' => 'حذف هذا الإعلان نهائيا؟',
        'st_wait'     => 'في الانتظار',
        'st_ok'       => 'مقبول',
        'st_no'       => 'مرفوض',
        'st_exp'      => 'منتهي',
        'err_csrf'    => 'انتهت الجلسة. المرجو المحاولة من جديد.',
        'ok_del'      => 'تم حذف الإعلان.',
        'currency'    => 'درهم',
    ],
];
$tx = $textes_page[$lang];

// Suppression d'une annonce (uniquement par son proprietaire)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_verifier()) {
        flash('danger', $tx['err_csrf']);
    } elseif (($_POST['action'] ?? '') === 'supprimer') {
        $id = (int) ($_POST['id'] ?? 0);

        $stmt = $pdo->prepare('SELECT id FROM annonces WHERE id = :id AND utilisateur_id = :u');
        $stmt->execute(['id' => $id, 'u' => $user['id']]);

        if ($stmt->fetch()) {
            $del = $pdo->prepare('DELETE FROM annonces WHERE id = :id AND utilisateur_id = :u');
            $del->execute(['id' => $id, 'u' => $user['id']]);
            supprimer_dossier(dossier_uploads((string) $id));
            flash('success', $tx['ok_del']);
        }
    }

    redirect('pages/mes-annonces.php');
}

$stmt = $pdo->prepare(
    'SELECT a.id, a.titre, a.prix, a.statut, a.motif_refus, a.date_creation, a.date_expiration,
            c.nom_fr AS cat_fr, c.nom_ar AS cat_ar,
            v.nom_fr AS ville_fr, v.nom_ar AS ville_ar,
            (SELECT i.chemin FROM images_annonces i
              WHERE i.annonce_id = a.id
              ORDER BY i.est_principale DESC, i.ordre ASC LIMIT 1) AS image
     FROM annonces a
     JOIN categories c ON c.id = a.categorie_id
     JOIN villes v ON v.id = a.ville_id
     WHERE a.utilisateur_id = :u
     ORDER BY a.date_creation DESC'
);
$stmt->execute(['u' => $user['id']]);
$annonces = $stmt->fetchAll();

$page_title = $tx['title'];
require_once __DIR__ . '/../includes/header.php';

$style_carte = 'background:var(--ac-surface); border:1px solid var(--ac-border); border-radius:var(--ac-radius); box-shadow:var(--ac-shadow);';
?>

<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-9">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 fw-bold mb-0"><?= e($tx['title']) ?></h1>
                <a class="btn btn-ac-orange" href="<?= BASE_URL ?>pages/publier.php">
                    <i class="bi bi-plus-circle me-1"></i><?= e($tx['publish']) ?>
                </a>
            </div>

            <?= flash_afficher() ?>

            <?php if (!$annonces): ?>
                <div class="p-4 text-center text-muted-ac" style="<?= $style_carte ?>">
                    <?= e($tx['empty']) ?>
                </div>
            <?php endif; ?>

            <?php foreach ($annonces as $a):
                $statut = $a['statut'];
                if ($statut === 'approuvee' && $a['date_expiration'] !== null && strtotime($a['date_expiration']) < time()) {
                    $statut = 'expiree';
                }
                $badges = [
                    'en_attente' => ['warning', $tx['st_wait']],
                    'approuvee'  => ['success', $tx['st_ok']],
                    'refusee'    => ['danger',  $tx['st_no']],
                    'expiree'    => ['secondary', $tx['st_exp']],
                ];
                [$classe_badge, $texte_badge] = $badges[$statut] ?? ['secondary', $statut];
                $prix_aff = rtrim(rtrim(number_format((float) $a['prix'], 2, ',', ' '), '0'), ',');
            ?>
                <div class="p-3 mb-3" style="<?= $style_carte ?>">
                    <div class="row g-3 align-items-center">

                        <div class="col-4 col-md-3">
                            <?php if ($a['image']): ?>
                                <img src="<?= e(BASE_URL . 'uploads/annonces/' . $a['image']) ?>" alt=""
                                     style="width:100%; aspect-ratio:4/3; object-fit:cover; border-radius:10px;">
                            <?php endif; ?>
                        </div>

                        <div class="col-8 col-md-9">
                            <div class="d-flex flex-wrap justify-content-between gap-2">
                                <h2 class="h6 fw-bold mb-1"><?= e($a['titre']) ?></h2>
                                <span class="badge text-bg-<?= $classe_badge ?>"><?= e($texte_badge) ?></span>
                            </div>

                            <p class="ac-price mb-1"><?= e($prix_aff) ?> <?= e($tx['currency']) ?></p>

                            <p class="text-muted-ac small mb-1">
                                <i class="bi bi-geo-alt"></i> <?= e($lang === 'ar' ? $a['ville_ar'] : $a['ville_fr']) ?>
                                &nbsp;·&nbsp;
                                <i class="bi bi-tag"></i> <?= e($lang === 'ar' ? $a['cat_ar'] : $a['cat_fr']) ?>
                            </p>

                            <p class="text-muted-ac small mb-2">
                                <?= e($tx['created']) ?> <?= e(date('d/m/Y', strtotime($a['date_creation']))) ?>
                                <?php if ($a['date_expiration'] !== null && $statut !== 'refusee'): ?>
                                    &nbsp;·&nbsp; <?= e($tx['expires']) ?> <?= e(date('d/m/Y', strtotime($a['date_expiration']))) ?>
                                <?php endif; ?>
                            </p>

                            <?php if ($statut === 'refusee' && !empty($a['motif_refus'])): ?>
                                <p class="small text-danger mb-2"><?= e($tx['reason']) ?> <?= e($a['motif_refus']) ?></p>
                            <?php endif; ?>

                            <form method="post" action="" class="m-0"
                                  onsubmit="return confirm(<?= e(json_encode($tx['confirm_del'], JSON_UNESCAPED_UNICODE)) ?>);">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="supprimer">
                                <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash me-1"></i><?= e($tx['delete']) ?>
                                </button>
                            </form>
                        </div>

                    </div>
                </div>
            <?php endforeach; ?>

        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>