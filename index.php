<?php
$page_title = 'Acheto';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/cartes.php';

$pdo = db();

// Categories actives (gerees depuis l'administration)
$categories = $pdo->query(
    'SELECT id, nom_fr, nom_ar, icone FROM categories WHERE actif = 1 ORDER BY id'
)->fetchAll();

// Annonces publiques (approuvees et non expirees)
function annonces_accueil(PDO $pdo, string $condition_extra, int $limite): array
{
    $stmt = $pdo->query(
        "SELECT a.id, a.titre, a.prix,
                c.nom_fr AS cat_fr, c.nom_ar AS cat_ar,
                v.nom_fr AS ville_fr, v.nom_ar AS ville_ar,
                (SELECT i.chemin FROM images_annonces i
                  WHERE i.annonce_id = a.id
                  ORDER BY i.est_principale DESC, i.ordre ASC LIMIT 1) AS image
         FROM annonces a
         JOIN categories c ON c.id = a.categorie_id
         JOIN villes v ON v.id = a.ville_id
         WHERE a.statut = 'approuvee' AND a.date_expiration > NOW() $condition_extra
         ORDER BY a.date_creation DESC, a.id DESC
         LIMIT " . (int) $limite
    );
    return $stmt->fetchAll();
}

$a_la_une  = annonces_accueil($pdo, 'AND a.a_la_une = 1', 4);
$dernieres = annonces_accueil($pdo, '', 8);

$favoris = ids_favoris($pdo, $user ? (int) $user['id'] : null);

$voir_tout = $lang === 'ar' ? 'عرض الكل' : 'Voir tout';
?>

<section class="ac-hero">
    <div class="container">
        <h1><?= t('hero_title') ?></h1>
        <p><?= t('hero_sub') ?></p>

        <form class="ac-search" action="<?= $base ?>pages/annonces.php" method="get">
            <input type="search" name="q" placeholder="<?= t('search_ph') ?>" aria-label="<?= t('search_btn') ?>">
            <button type="submit" class="btn btn-ac-orange">
                <i class="bi bi-search me-1"></i><?= t('search_btn') ?>
            </button>
        </form>

        <a class="btn btn-light fw-bold" href="<?= $base ?>pages/publier.php">
            <i class="bi bi-plus-circle me-1"></i><?= t('publish') ?>
        </a>
    </div>
</section>

<section class="ac-section">
    <div class="container">
        <h2 class="ac-section-title reveal"><?= t('cat_title') ?></h2>
        <div class="row g-3">
            <?php foreach ($categories as $c): ?>
                <div class="col-6 col-md-4 col-lg-3 reveal">
                    <a class="ac-cat" href="<?= $base ?>pages/annonces.php?categorie=<?= (int) $c['id'] ?>">
                        <i class="bi <?= e($c['icone']) ?>"></i>
                        <?= e($lang === 'ar' ? $c['nom_ar'] : $c['nom_fr']) ?>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php if ($a_la_une): ?>
<section class="ac-section">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3 reveal">
            <h2 class="ac-section-title mb-0"><?= t('featured_title') ?></h2>
            <a href="<?= $base ?>pages/annonces.php" class="text-decoration-none fw-semibold"><?= e($voir_tout) ?></a>
        </div>
        <div class="row g-3">
            <?php foreach ($a_la_une as $a): ?>
                <div class="col-6 col-md-4 col-lg-3 reveal">
                    <?= carte_annonce($a, $favoris, 'index.php', $lang) ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="ac-section">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3 reveal">
            <h2 class="ac-section-title mb-0"><?= t('latest_title') ?></h2>
            <a href="<?= $base ?>pages/annonces.php" class="text-decoration-none fw-semibold"><?= e($voir_tout) ?></a>
        </div>

        <?php if (!$dernieres): ?>
            <p class="text-muted-ac reveal"><?= t('empty_ads') ?></p>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($dernieres as $a): ?>
                    <div class="col-6 col-md-4 col-lg-3 reveal">
                        <?= carte_annonce($a, $favoris, 'index.php', $lang) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="ac-section">
    <div class="container">
        <h2 class="ac-section-title text-center reveal"><?= t('how_title') ?></h2>
        <div class="row g-3">
            <div class="col-12 col-md-4 reveal">
                <div class="ac-step">
                    <div class="ac-step-num">1</div>
                    <h5><?= t('step1_t') ?></h5>
                    <p class="text-muted-ac"><?= t('step1_p') ?></p>
                </div>
            </div>
            <div class="col-12 col-md-4 reveal">
                <div class="ac-step">
                    <div class="ac-step-num">2</div>
                    <h5><?= t('step2_t') ?></h5>
                    <p class="text-muted-ac"><?= t('step2_p') ?></p>
                </div>
            </div>
            <div class="col-12 col-md-4 reveal">
                <div class="ac-step">
                    <div class="ac-step-num">3</div>
                    <h5><?= t('step3_t') ?></h5>
                    <p class="text-muted-ac"><?= t('step3_p') ?></p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="ac-section">
    <div class="container">
        <div class="ac-cta reveal">
            <h2 class="fw-bold"><?= t('cta_title') ?></h2>
            <p><?= t('cta_text') ?></p>
            <a class="btn btn-ac-orange btn-lg" href="<?= $base ?>pages/publier.php">
                <i class="bi bi-plus-circle me-1"></i><?= t('publish') ?>
            </a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>