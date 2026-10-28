<?php
require_once __DIR__ . '/../includes/lang.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cartes.php';

$pdo  = db();
$user = utilisateur_courant();

$textes_page = [
    'fr' => [
        'title'       => 'Annonces',
        'search_ph'   => 'Que cherchez-vous ?',
        'search_btn'  => 'Rechercher',
        'filters'     => 'Filtres',
        'count'       => 'annonce(s)',
        'category'    => 'Catégorie',
        'all_cats'    => 'Toutes les catégories',
        'city'        => 'Ville',
        'all_cities'  => 'Toutes les villes',
        'price'       => 'Prix (DH)',
        'min'         => 'Min',
        'max'         => 'Max',
        'sort'        => 'Trier par',
        'recent'      => 'Plus récentes',
        'price_asc'   => 'Prix croissant',
        'price_desc'  => 'Prix décroissant',
        'reset'       => 'Réinitialiser',
        'apply'       => 'Voir les résultats',
        'none'        => 'Aucune annonce ne correspond à votre recherche.',
        'prev'        => 'Précédent',
        'next'        => 'Suivant',
    ],
    'ar' => [
        'title'       => 'الإعلانات',
        'search_ph'   => 'عن ماذا تبحث؟',
        'search_btn'  => 'بحث',
        'filters'     => 'تصفية',
        'count'       => 'إعلان',
        'category'    => 'الفئة',
        'all_cats'    => 'كل الفئات',
        'city'        => 'المدينة',
        'all_cities'  => 'كل المدن',
        'price'       => 'الثمن (درهم)',
        'min'         => 'الأدنى',
        'max'         => 'الأقصى',
        'sort'        => 'ترتيب حسب',
        'recent'      => 'الأحدث',
        'price_asc'   => 'الثمن تصاعديا',
        'price_desc'  => 'الثمن تنازليا',
        'reset'       => 'إعادة التعيين',
        'apply'       => 'عرض النتائج',
        'none'        => 'لا توجد إعلانات مطابقة لبحثك.',
        'prev'        => 'السابق',
        'next'        => 'التالي',
    ],
];
$tx = $textes_page[$lang];

$categories = $pdo->query('SELECT id, nom_fr, nom_ar FROM categories WHERE actif = 1 ORDER BY id')->fetchAll();
$villes     = $pdo->query('SELECT id, nom_fr, nom_ar FROM villes ORDER BY nom_fr')->fetchAll();

// Lecture sure des parametres GET (toujours du texte)
$get = function (string $cle): string {
    return is_string($_GET[$cle] ?? null) ? trim($_GET[$cle]) : '';
};

$q     = mb_substr($get('q'), 0, 100);
$cat   = (int) $get('categorie');
$ville = (int) $get('ville');
$pmin  = $get('prix_min');
$pmax  = $get('prix_max');
$tri   = in_array($get('tri'), ['recent', 'prix_asc', 'prix_desc'], true) ? $get('tri') : 'recent';
$page  = max(1, (int) $get('page'));

if ($pmin !== '' && (!is_numeric($pmin) || (float) $pmin < 0)) {
    $pmin = '';
}
if ($pmax !== '' && (!is_numeric($pmax) || (float) $pmax < 0)) {
    $pmax = '';
}

// Conditions SQL (seules les annonces approuvees et non expirees)
$where  = ["a.statut = 'approuvee'", 'a.date_expiration > NOW()'];
$params = [];

$mots = array_slice(preg_split('/\s+/u', $q, -1, PREG_SPLIT_NO_EMPTY) ?: [], 0, 5);
foreach ($mots as $i => $mot) {
    $motif = '%' . addcslashes($mot, '%_\\') . '%';
    $where[] = "(a.titre LIKE :t$i OR a.description LIKE :d$i)";
    $params["t$i"] = $motif;
    $params["d$i"] = $motif;
}
if ($cat > 0) {
    $where[]       = 'a.categorie_id = :cat';
    $params['cat'] = $cat;
}
if ($ville > 0) {
    $where[]         = 'a.ville_id = :ville';
    $params['ville'] = $ville;
}
if ($pmin !== '') {
    $where[]         = 'a.prix >= :pmin';
    $params['pmin']  = $pmin;
}
if ($pmax !== '') {
    $where[]         = 'a.prix <= :pmax';
    $params['pmax']  = $pmax;
}

$condition = implode(' AND ', $where);

$ordres = [
    'recent'    => 'a.date_creation DESC, a.id DESC',
    'prix_asc'  => 'a.prix ASC, a.id DESC',
    'prix_desc' => 'a.prix DESC, a.id DESC',
];

// Pagination
$par_page = 12;
$stmt = $pdo->prepare("SELECT COUNT(*) FROM annonces a WHERE $condition");
$stmt->execute($params);
$total       = (int) $stmt->fetchColumn();
$total_pages = max(1, (int) ceil($total / $par_page));
$page        = min($page, $total_pages);
$decalage    = ($page - 1) * $par_page;

$stmt = $pdo->prepare(
    "SELECT a.id, a.titre, a.prix,
            c.nom_fr AS cat_fr, c.nom_ar AS cat_ar,
            v.nom_fr AS ville_fr, v.nom_ar AS ville_ar,
            (SELECT i.chemin FROM images_annonces i
              WHERE i.annonce_id = a.id
              ORDER BY i.est_principale DESC, i.ordre ASC LIMIT 1) AS image
     FROM annonces a
     JOIN categories c ON c.id = a.categorie_id
     JOIN villes v ON v.id = a.ville_id
     WHERE $condition
     ORDER BY {$ordres[$tri]}
     LIMIT $par_page OFFSET $decalage"
);
$stmt->execute($params);
$annonces = $stmt->fetchAll();

$favoris = ids_favoris($pdo, $user ? (int) $user['id'] : null);

// Liens (conservent les filtres)
$filtres = array_filter([
    'q'         => $q,
    'categorie' => $cat > 0 ? $cat : '',
    'ville'     => $ville > 0 ? $ville : '',
    'prix_min'  => $pmin,
    'prix_max'  => $pmax,
    'tri'       => $tri !== 'recent' ? $tri : '',
], function ($v) {
    return $v !== '' && $v !== null;
});

function qs(array $f): string
{
    return $f ? '?' . http_build_query($f, '', '&', PHP_QUERY_RFC3986) : '';
}

$retour = 'pages/annonces.php' . qs($page > 1 ? $filtres + ['page' => $page] : $filtres);

$page_title = $tx['title'];
require_once __DIR__ . '/../includes/header.php';
?>

<main class="container py-4">

    <h1 class="h3 fw-bold mb-3"><?= e($tx['title']) ?></h1>

    <form method="get" action="">

        <div class="d-flex gap-2 mb-3">
            <input type="search" class="form-control" name="q" value="<?= e($q) ?>"
                   placeholder="<?= e($tx['search_ph']) ?>" maxlength="100" aria-label="<?= e($tx['search_btn']) ?>">
            <button type="submit" class="btn btn-ac-blue" aria-label="<?= e($tx['search_btn']) ?>">
                <i class="bi bi-search"></i>
            </button>
            <button type="button" class="btn btn-ac-orange text-nowrap" data-bs-toggle="offcanvas" data-bs-target="#filtres">
                <i class="bi bi-sliders me-1"></i><?= e($tx['filters']) ?>
            </button>
        </div>

        <div class="offcanvas offcanvas-bottom" tabindex="-1" id="filtres"
             style="height:auto; max-height:90vh; background:var(--ac-surface); color:var(--ac-text); border-radius:16px 16px 0 0;">
            <div class="offcanvas-header">
                <h2 class="offcanvas-title h5 fw-bold"><?= e($tx['filters']) ?></h2>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <div class="row g-3">

                    <div class="col-12 col-md-6">
                        <label for="f_cat" class="form-label"><?= e($tx['category']) ?></label>
                        <select class="form-select" id="f_cat" name="categorie">
                            <option value=""><?= e($tx['all_cats']) ?></option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= (int) $c['id'] ?>" <?= $cat === (int) $c['id'] ? 'selected' : '' ?>>
                                    <?= e($lang === 'ar' ? $c['nom_ar'] : $c['nom_fr']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="f_ville" class="form-label"><?= e($tx['city']) ?></label>
                        <select class="form-select" id="f_ville" name="ville">
                            <option value=""><?= e($tx['all_cities']) ?></option>
                            <?php foreach ($villes as $v): ?>
                                <option value="<?= (int) $v['id'] ?>" <?= $ville === (int) $v['id'] ? 'selected' : '' ?>>
                                    <?= e($lang === 'ar' ? $v['nom_ar'] : $v['nom_fr']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label"><?= e($tx['price']) ?></label>
                        <div class="d-flex gap-2">
                            <input type="number" class="form-control" name="prix_min" min="0" step="1"
                                   value="<?= e($pmin) ?>" placeholder="<?= e($tx['min']) ?>" dir="ltr">
                            <input type="number" class="form-control" name="prix_max" min="0" step="1"
                                   value="<?= e($pmax) ?>" placeholder="<?= e($tx['max']) ?>" dir="ltr">
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="f_tri" class="form-label"><?= e($tx['sort']) ?></label>
                        <select class="form-select" id="f_tri" name="tri">
                            <option value="recent" <?= $tri === 'recent' ? 'selected' : '' ?>><?= e($tx['recent']) ?></option>
                            <option value="prix_asc" <?= $tri === 'prix_asc' ? 'selected' : '' ?>><?= e($tx['price_asc']) ?></option>
                            <option value="prix_desc" <?= $tri === 'prix_desc' ? 'selected' : '' ?>><?= e($tx['price_desc']) ?></option>
                        </select>
                    </div>

                    <div class="col-12 d-flex gap-2">
                        <a href="<?= BASE_URL ?>pages/annonces.php" class="btn btn-outline-secondary"><?= e($tx['reset']) ?></a>
                        <button type="submit" class="btn btn-ac-orange flex-fill"><?= e($tx['apply']) ?></button>
                    </div>

                </div>
            </div>
        </div>
    </form>

    <p class="text-muted-ac small mb-3"><?= (int) $total ?> <?= e($tx['count']) ?></p>

    <?php if (!$annonces): ?>
        <div class="p-4 text-center text-muted-ac" style="background:var(--ac-surface); border:1px solid var(--ac-border); border-radius:var(--ac-radius);">
            <?= e($tx['none']) ?>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($annonces as $a): ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <?= carte_annonce($a, $favoris, $retour, $lang) ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($total_pages > 1): ?>
        <nav class="mt-4" aria-label="Pagination">
            <ul class="pagination justify-content-center flex-wrap">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= e(BASE_URL . 'pages/annonces.php' . qs($filtres + ['page' => max(1, $page - 1)])) ?>"><?= e($tx['prev']) ?></a>
                </li>

                <?php for ($p = max(1, $page - 2); $p <= min($total_pages, $page + 2); $p++): ?>
                    <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                        <a class="page-link" href="<?= e(BASE_URL . 'pages/annonces.php' . qs($filtres + ['page' => $p])) ?>"><?= $p ?></a>
                    </li>
                <?php endfor; ?>

                <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= e(BASE_URL . 'pages/annonces.php' . qs($filtres + ['page' => min($total_pages, $page + 1)])) ?>"><?= e($tx['next']) ?></a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>