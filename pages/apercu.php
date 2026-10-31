<?php
require_once __DIR__ . '/../includes/lang.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/uploads.php';

$user = exiger_connexion();
$pdo  = db();

$textes_page = [
    'fr' => [
        'title'      => 'Aperçu de l\'annonce',
        'intro'      => 'Vérifiez votre annonce avant de l\'envoyer. Elle sera publiée après validation par un administrateur.',
        'contact'    => 'Contact',
        'whatsapp'   => 'Disponible sur WhatsApp',
        'edit'       => 'Modifier',
        'cancel'     => 'Annuler',
        'confirm'    => 'Confirmer l\'envoi',
        'err_csrf'   => 'Session expirée. Veuillez réessayer.',
        'err_save'   => 'Impossible d\'enregistrer l\'annonce. Veuillez réessayer.',
        'ok_send'    => 'Votre annonce a été envoyée. Elle est en attente de validation.',
        'ok_cancel'  => 'Brouillon supprimé.',
        'currency'   => 'DH',
    ],
    'ar' => [
        'title'      => 'معاينة الإعلان',
        'intro'      => 'تحقق من إعلانك قبل إرساله. سيتم نشره بعد مراجعته من طرف المشرف.',
        'contact'    => 'التواصل',
        'whatsapp'   => 'متوفر على واتساب',
        'edit'       => 'تعديل',
        'cancel'     => 'إلغاء',
        'confirm'    => 'تأكيد الإرسال',
        'err_csrf'   => 'انتهت الجلسة. المرجو المحاولة من جديد.',
        'err_save'   => 'تعذر حفظ الإعلان. المرجو المحاولة من جديد.',
        'ok_send'    => 'تم إرسال إعلانك. هو في انتظار المراجعة.',
        'ok_cancel'  => 'تم حذف المسودة.',
        'currency'   => 'درهم',
    ],
];
$tx = $textes_page[$lang];

// Brouillon obligatoire
$b = $_SESSION['brouillon'] ?? null;
if (!$b) {
    redirect('pages/publier.php');
}

// Securite : le dossier temporaire doit appartenir a cet utilisateur
$motif_dossier = '/^tmp_' . (int) $user['id'] . '_[a-f0-9]{16}$/';
if (!is_string($b['dossier'] ?? null) || !preg_match($motif_dossier, $b['dossier'])) {
    unset($_SESSION['brouillon']);
    redirect('pages/publier.php');
}

$images = [];
foreach (($b['images'] ?? []) as $nom) {
    if (is_string($nom)
        && preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $nom)
        && image_temporaire_existe($b['dossier'], $nom)) {
        $images[] = $nom;
    }
}

if (!$images) {
    supprimer_dossier(dossier_uploads($b['dossier']));
    unset($_SESSION['brouillon']);
    redirect('pages/publier.php');
}

// Categorie et ville
$stmt = $pdo->prepare('SELECT nom_fr, nom_ar FROM categories WHERE id = :id AND actif = 1');
$stmt->execute(['id' => $b['categorie_id']]);
$categorie = $stmt->fetch();

$stmt = $pdo->prepare('SELECT nom_fr, nom_ar FROM villes WHERE id = :id');
$stmt->execute(['id' => $b['ville_id']]);
$ville = $stmt->fetch();

if (!$categorie || !$ville) {
    redirect('pages/publier.php');
}

$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_verifier()) {
        $erreurs[] = $tx['err_csrf'];
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'annuler') {
            supprimer_dossier(dossier_uploads($b['dossier']));
            unset($_SESSION['brouillon']);
            flash('info', $tx['ok_cancel']);
            redirect('pages/publier.php');
        }

        if ($action === 'confirmer') {
            $dossier_final = null;

            try {
                $pdo->beginTransaction();

                $ins = $pdo->prepare(
                    'INSERT INTO annonces
                        (utilisateur_id, categorie_id, ville_id, titre, description, prix, telephone_contact, whatsapp, statut)
                     VALUES
                        (:u, :c, :v, :titre, :descr, :prix, :tel, :wa, \'en_attente\')'
                );
                $ins->execute([
                    'u'     => $user['id'],
                    'c'     => $b['categorie_id'],
                    'v'     => $b['ville_id'],
                    'titre' => $b['titre'],
                    'descr' => $b['description'],
                    'prix'  => $b['prix'],
                    'tel'   => $b['telephone'],
                    'wa'    => $b['whatsapp'] ? $b['telephone'] : null,
                ]);

                $annonce_id    = (int) $pdo->lastInsertId();
                $dossier_final = dossier_uploads((string) $annonce_id);

                $ins_img = $pdo->prepare(
                    'INSERT INTO images_annonces (annonce_id, chemin, est_principale, ordre)
                     VALUES (:a, :chemin, :p, :o)'
                );

                foreach ($images as $i => $nom) {
                    // Cloudinary : URL complete ; local : "id/nom.jpg" (fichier deplace)
                    $chemin = finaliser_image($b['dossier'], $nom, $annonce_id);
                    $ins_img->execute([
                        'a'      => $annonce_id,
                        'chemin' => $chemin,
                        'p'      => $i === 0 ? 1 : 0,
                        'o'      => $i,
                    ]);
                }

                $pdo->commit();

                supprimer_dossier(dossier_uploads($b['dossier']));
                unset($_SESSION['brouillon']);
                flash('success', $tx['ok_send']);
                redirect('pages/mes-annonces.php');

            } catch (Throwable $ex) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                if ($dossier_final !== null) {
                    supprimer_dossier($dossier_final);
                }
                error_log('Acheto - confirmation annonce : ' . $ex->getMessage());
                $erreurs[] = $tx['err_save'];
            }
        }
    }
}

$page_title = $tx['title'];
require_once __DIR__ . '/../includes/header.php';

$style_carte = 'background:var(--ac-surface); border:1px solid var(--ac-border); border-radius:var(--ac-radius); box-shadow:var(--ac-shadow);';
$prix_aff    = rtrim(rtrim(number_format((float) $b['prix'], 2, ',', ' '), '0'), ',');
?>

<style>
    .apercu-main { width: 100%; aspect-ratio: 4 / 3; object-fit: cover; border-radius: var(--ac-radius); background: var(--ac-bg); }
    .apercu-thumb { width: 72px; height: 72px; object-fit: cover; border-radius: 8px; border: 2px solid var(--ac-border); cursor: pointer; }
    .apercu-thumb.on { border-color: var(--ac-blue); }
</style>

<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">

            <h1 class="h3 fw-bold mb-2"><?= e($tx['title']) ?></h1>
            <p class="text-muted-ac mb-4"><?= e($tx['intro']) ?></p>

            <?= flash_afficher() ?>

            <?php if ($erreurs): ?>
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($erreurs as $erreur): ?>
                            <li><?= e($erreur) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="p-4 mb-4" style="<?= $style_carte ?>">

                <img class="apercu-main mb-2" id="photoPrincipale" src="<?= e(url_image_temporaire($b['dossier'], $images[0])) ?>" alt="<?= e($b['titre']) ?>">

                <?php if (count($images) > 1): ?>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <?php foreach ($images as $i => $nom): ?>
                            <img class="apercu-thumb <?= $i === 0 ? 'on' : '' ?>" src="<?= e(url_image_temporaire($b['dossier'], $nom)) ?>" alt="">
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <p class="ac-price fs-3 mb-1"><?= e($prix_aff) ?> <?= e($tx['currency']) ?></p>
                <h2 class="h4 fw-bold"><?= e($b['titre']) ?></h2>

                <p class="text-muted-ac small mb-3">
                    <i class="bi bi-geo-alt"></i> <?= e($lang === 'ar' ? $ville['nom_ar'] : $ville['nom_fr']) ?>
                    &nbsp;·&nbsp;
                    <i class="bi bi-tag"></i> <?= e($lang === 'ar' ? $categorie['nom_ar'] : $categorie['nom_fr']) ?>
                </p>

                <p style="white-space: pre-line;"><?= e($b['description']) ?></p>

                <hr>

                <h3 class="h6 fw-bold"><?= e($tx['contact']) ?></h3>
                <p class="mb-1" dir="ltr"><i class="bi bi-telephone"></i> <?= e($b['telephone']) ?></p>
                <?php if ($b['whatsapp']): ?>
                    <p class="mb-0 small"><i class="bi bi-whatsapp" style="color:#25D366;"></i> <?= e($tx['whatsapp']) ?></p>
                <?php endif; ?>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <a href="<?= BASE_URL ?>pages/publier.php" class="btn btn-outline-secondary"><?= e($tx['edit']) ?></a>

                <form method="post" action="" class="m-0">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="annuler">
                    <button type="submit" class="btn btn-outline-danger"><?= e($tx['cancel']) ?></button>
                </form>

                <form method="post" action="" class="m-0 flex-fill">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="confirmer">
                    <button type="submit" class="btn btn-ac-blue w-100"><?= e($tx['confirm']) ?></button>
                </form>
            </div>

        </div>
    </div>
</main>

<script>
(function () {
    var principale = document.getElementById('photoPrincipale');
    var vignettes  = document.querySelectorAll('.apercu-thumb');
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