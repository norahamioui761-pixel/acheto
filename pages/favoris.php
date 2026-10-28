<?php
require_once __DIR__ . '/../includes/lang.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cartes.php';

$user = exiger_connexion();
$pdo  = db();

$textes_page = [
    'fr' => [
        'title'  => 'Mes favoris',
        'empty'  => 'Vous n\'avez pas encore d\'annonce en favori.',
        'browse' => 'Parcourir les annonces',
    ],
    'ar' => [
        'title'  => 'المفضلة',
        'empty'  => 'ليس لديك أي إعلان في المفضلة بعد.',
        'browse' => 'تصفح الإعلانات',
    ],
];
$tx = $textes_page[$lang];

$stmt = $pdo->prepare(
    "SELECT a.id, a.titre, a.prix,
            c.nom_fr AS cat_fr, c.nom_ar AS cat_ar,
            v.nom_fr AS ville_fr, v.nom_ar AS ville_ar,
            (SELECT i.chemin FROM images_annonces i
              WHERE i.annonce_id = a.id
              ORDER BY i.est_principale DESC, i.ordre ASC LIMIT 1) AS image
     FROM favoris f
     JOIN annonces a ON a.id = f.annonce_id
     JOIN categories c ON c.id = a.categorie_id
     JOIN villes v ON v.id = a.ville_id
     WHERE f.utilisateur_id = :u
       AND a.statut = 'approuvee'
       AND a.date_expiration > NOW()
     ORDER BY f.date_ajout DESC"
);
$stmt->execute(['u' => $user['id']]);
$annonces = $stmt->fetchAll();

$favoris = array_map('intval', array_column($annonces, 'id'));

$page_title = $tx['title'];
require_once __DIR__ . '/../includes/header.php';
?>

<main class="container py-4">

    <h1 class="h3 fw-bold mb-4"><?= e($tx['title']) ?></h1>

    <?= flash_afficher() ?>

    <?php if (!$annonces): ?>
        <div class="p-4 text-center" style="background:var(--ac-surface); border:1px solid var(--ac-border); border-radius:var(--ac-radius);">
            <p class="text-muted-ac"><?= e($tx['empty']) ?></p>
            <a class="btn btn-ac-blue" href="<?= BASE_URL ?>pages/annonces.php"><?= e($tx['browse']) ?></a>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($annonces as $a): ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <?= carte_annonce($a, $favoris, 'pages/favoris.php', $lang) ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>