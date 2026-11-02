<?php
require_once __DIR__ . '/../includes/lang.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cartes.php';

$pdo  = db();
$user = utilisateur_courant();

$textes_page = [
    'fr' => [
        'back'          => 'Retour aux annonces',
        'posted'        => 'Publiée le',
        'seller'        => 'Vendeur',
        'contact'       => 'Contacter le vendeur',
        'call'          => 'Appeler',
        'whatsapp'      => 'WhatsApp',
        'message'       => 'Message',
        'wa_text'       => 'Bonjour, je suis intéressé(e) par votre annonce : ',
        'fav_add'       => 'Ajouter aux favoris',
        'fav_remove'    => 'Retirer des favoris',
        'desc'          => 'Description',
        'not_found'     => 'Cette annonce est introuvable ou n\'est plus disponible.',
        'not_public'    => 'Cette annonce n\'est pas publique (statut :',
        'st_en_attente' => 'en attente',
        'st_refusee'    => 'refusée',
        'st_expiree'    => 'expirée',
    ],
    'ar' => [
        'back'          => 'العودة إلى الإعلانات',
        'posted'        => 'نشر في',
        'seller'        => 'البائع',
        'contact'       => 'تواصل مع البائع',
        'call'          => 'اتصال',
        'whatsapp'      => 'واتساب',
        'message'       => 'رسالة',
        'wa_text'       => 'مرحبا، أنا مهتم بإعلانك: ',
        'fav_add'       => 'إضافة إلى المفضلة',
        'fav_remove'    => 'إزالة من المفضلة',
        'desc'          => 'الوصف',
        'not_found'     => 'هذا الإعلان غير موجود أو لم يعد متاحا.',
        'not_public'    => 'هذا الإعلان غير عمومي (الحالة:',
        'st_en_attente' => 'في الانتظار',
        'st_refusee'    => 'مرفوض',
        'st_expiree'    => 'منتهي',
    ],
];
$tx = $textes_page[$lang];

// Numero marocain -> format international sans "+" (ex : 212612345678)
function numero_international(string $tel): ?string
{
    $t = preg_replace('/[\s.\-]/', '', $tel);

    if (preg_match('/^0([5-7][0-9]{8})$/', $t, $m)) {
        return '212' . $m[1];
    }
    if (preg_match('/^\+212([5-7][0-9]{8})$/', $t, $m)) {
        return '212' . $m[1];
    }

    return null;
}

$id = is_string($_GET['id'] ?? null) ? (int) $_GET['id'] : 0;

$stmt = $pdo->prepare(
    'SELECT a.*,
            c.nom_fr AS cat_fr, c.nom_ar AS cat_ar,
            v.nom_fr AS ville_fr, v.nom_ar AS ville_ar,
            u.nom AS auteur
     FROM annonces a
     JOIN categories c ON c.id = a.categorie_id
     JOIN villes v ON v.id = a.ville_id
     JOIN utilisateurs u ON u.id = a.utilisateur_id
     WHERE a.id = :id'
);
$stmt->execute(['id' => $id]);
$a = $stmt->fetch();

$public = $a
    && $a['statut'] === 'approuvee'
    && $a['date_expiration'] !== null
    && strtotime($a['date_expiration']) > time();

$proprietaire = $a && $user
    && ((int) $user['id'] === (int) $a['utilisateur_id'] || $user['role'] === 'admin');

if (!$a || (!$public && !$proprietaire)) {
    http_response_code(404);
    $page_title = 'Annonce introuvable';
    require_once __DIR__ . '/../includes/header.php';
    ?>
    <main class="container py-5 text-center">
        <p class="fs-5"><?= e($tx['not_found']) ?></p>
        <a class="btn btn-ac-blue" href="<?= BASE_URL ?>pages/annonces.php"><?= e($tx['back']) ?></a>
    </main>
    <?php
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Photos
$stmt = $pdo->prepare(
    'SELECT chemin FROM images_annonces WHERE annonce_id = :id ORDER BY est_principale DESC, ordre ASC'
);
$stmt->execute(['id' => $id]);
$photos = $stmt->fetchAll(PDO::FETCH_COLUMN);

$favoris = ids_favoris($pdo, $user ? (int) $user['id'] : null);
$est_fav = in_array($id, $favoris, true);

// Contact
$tel      = (string) $a['telephone_contact'];
$tel_intl = numero_international($tel);
$lien_tel = $tel_intl ? 'tel:+' . $tel_intl : null;

$wa_intl = !empty($a['whatsapp']) ? numero_international((string) $a['whatsapp']) : null;
$lien_wa = $wa_intl
    ? 'https://wa.me/' . $wa_intl . '?text=' . rawurlencode($tx['wa_text'] . $a['titre'])
    : null;

// Le message est possible pour une annonce publique, sauf pour son propre auteur
$est_auteur  = $user && (int) $user['id'] === (int) $a['utilisateur_id'];
$peut_ecrire = $public && !$est_auteur;

$date_pub = $a['date_approbation'] ?? $a['date_creation'];

$page_title = $a['titre'];
require_once __DIR__ . '/../includes/header.php';

$style_carte = 'background:var(--ac-surface); border:1px solid var(--ac-border); border-radius:var(--ac-radius); box-shadow:var(--ac-shadow);';

$statut_affiche = $a['statut'] === 'approuvee' ? 'expiree' : $a['statut'];
?>

<style>
    .det-main { width: 100%; aspect-ratio: 4 / 3; object-fit: cover; border-radius: var(--ac-radius); background: var(--ac-bg); }
    .det-thumb { width: 72px; height: 72px; object-fit: cover; border-radius: 8px; border: 2px solid var(--ac-border); cursor: pointer; }
    .det-thumb.on { border-color: var(--ac-blue); }
</style>

<main class="container py-4">

    <a href="<?= BASE_URL ?>pages/annonces.php" class="text-decoration-none small">
        <i class="bi bi-arrow-<?= $dir === 'rtl' ? 'right' : 'left' ?>"></i> <?= e($tx['back']) ?>
    </a>

    <?= flash_afficher() ?>

    <?php if (!$public): ?>
        <div class="alert alert-info mt-3" role="alert">
            <?= e($tx['not_public']) ?> <?= e($tx['st_' . $statut_affiche] ?? $statut_affiche) ?>)
        </div>
    <?php endif; ?>

    <div class="row g-4 mt-1">

        <div class="col-12 col-lg-7">
            <?php if ($photos): ?>
                <img class="det-main mb-2" id="photoPrincipale"
                     src="<?= e(url_image($photos[0])) ?>" alt="<?= e($a['titre']) ?>">

                <?php if (count($photos) > 1): ?>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($photos as $i => $p): ?>
                            <img class="det-thumb <?= $i === 0 ? 'on' : '' ?>"
                                 src="<?= e(url_image($p)) ?>" alt="">
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="col-12 col-lg-5">
            <div class="p-4" style="<?= $style_carte ?>">

                <p class="ac-price fs-3 mb-1"><?= e(formater_prix($a['prix'], $lang)) ?></p>
                <h1 class="h4 fw-bold"><?= e($a['titre']) ?></h1>

                <p class="text-muted-ac small mb-3">
                    <i class="bi bi-geo-alt"></i> <?= e($lang === 'ar' ? $a['ville_ar'] : $a['ville_fr']) ?>
                    &nbsp;·&nbsp;
                    <i class="bi bi-tag"></i> <?= e($lang === 'ar' ? $a['cat_ar'] : $a['cat_fr']) ?>
                    <br>
                    <i class="bi bi-calendar3"></i> <?= e($tx['posted']) ?> <?= e(date('d/m/Y', strtotime($date_pub))) ?>
                </p>

                <p class="small mb-3">
                    <i class="bi bi-person-circle"></i> <?= e($tx['seller']) ?> : <strong><?= e($a['auteur']) ?></strong>
                </p>

                <?php if ($lien_tel || $lien_wa || $peut_ecrire): ?>
                    <button class="btn btn-ac-blue w-100 mb-2" type="button" data-bs-toggle="collapse"
                            data-bs-target="#optionsContact" aria-expanded="false" aria-controls="optionsContact">
                        <i class="bi bi-telephone me-1"></i><?= e($tx['contact']) ?>
                    </button>

                    <div class="collapse" id="optionsContact">
                        <div class="row g-2 mb-2">
                            <?php if ($lien_wa): ?>
                                <div class="col">
                                    <a class="btn w-100" style="background:#25D366; color:#073b1c;"
                                       href="<?= e($lien_wa) ?>" target="_blank" rel="noopener noreferrer">
                                        <i class="bi bi-whatsapp me-1"></i><?= e($tx['whatsapp']) ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                            <?php if ($lien_tel): ?>
                                <div class="col">
                                    <a class="btn btn-outline-primary w-100" href="<?= e($lien_tel) ?>">
                                        <i class="bi bi-telephone me-1"></i><?= e($tx['call']) ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                            <?php if ($peut_ecrire): ?>
                                <div class="col">
                                    <a class="btn btn-outline-secondary w-100"
                                       href="<?= BASE_URL ?>pages/message.php?id=<?= (int) $a['id'] ?>">
                                        <i class="bi bi-envelope me-1"></i><?= e($tx['message']) ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if ($lien_tel || $lien_wa): ?>
                            <p class="text-center small text-muted-ac" dir="ltr"><?= e($tel) ?></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ($public): ?>
                    <form method="post" action="<?= BASE_URL ?>pages/favori.php" class="m-0">
                        <?= csrf_field() ?>
                        <input type="hidden" name="annonce_id" value="<?= (int) $a['id'] ?>">
                        <input type="hidden" name="retour" value="pages/details.php?id=<?= (int) $a['id'] ?>">
                        <button type="submit" class="btn btn-outline-secondary w-100">
                            <i class="bi <?= $est_fav ? 'bi-heart-fill text-danger' : 'bi-heart' ?> me-1"></i>
                            <?= e($est_fav ? $tx['fav_remove'] : $tx['fav_add']) ?>
                        </button>
                    </form>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <div class="p-4 mt-4" style="<?= $style_carte ?>">
        <h2 class="h5 fw-bold mb-3"><?= e($tx['desc']) ?></h2>
        <p class="mb-0" style="white-space: pre-line;"><?= e($a['description']) ?></p>
    </div>

</main>

<script>
(function () {
    var principale = document.getElementById('photoPrincipale');
    var vignettes  = document.querySelectorAll('.det-thumb');
    if (!principale) { return; }
    vignettes.forEach(function (v) {
        v.addEventListener('click', function () {
            principale.src = v.src;
            vignettes.forEach(function (x) { x.classList.remove('on'); });
            v.classList.add('on');
        });
    });
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>