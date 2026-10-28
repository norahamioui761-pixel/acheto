<?php
require_once __DIR__ . '/../includes/lang.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/uploads.php';

$user = exiger_connexion();
$pdo  = db();

$textes_page = [
    'fr' => [
        'title'        => 'Publier une annonce',
        'st_cat'       => 'Catégorie',
        'st_details'   => 'Détails',
        'st_photos'    => 'Photos',
        'st_contact'   => 'Contact',
        's1_title'     => 'Choisissez une catégorie',
        's2_title'     => 'Décrivez votre annonce',
        's3_title'     => 'Ajoutez des photos',
        's4_title'     => 'Vos coordonnées',
        'f_title'      => 'Titre',
        'f_desc'       => 'Description',
        'f_price'      => 'Prix (DH)',
        'f_city'       => 'Ville',
        'city_choose'  => 'Choisissez une ville',
        'photos_hint'  => 'Jusqu\'à 5 photos (JPG, PNG ou WebP, 2 Mo maximum chacune).',
        'draft_note'   => 'Vos informations ont été conservées. Ajoutez de nouveau vos photos.',
        'f_phone'      => 'Téléphone de contact',
        'f_wa'         => 'Ce numéro est aussi sur WhatsApp',
        'back'         => 'Retour',
        'next'         => 'Continuer',
        'preview'      => 'Prévisualiser',
        'cat_required' => 'Veuillez choisir une catégorie.',
        'js_max'       => 'Maximum 5 photos.',
        'js_big'       => 'Chaque photo doit faire 2 Mo maximum.',
        'err_csrf'     => 'Session expirée. Veuillez réessayer.',
        'err_size'     => 'Les fichiers envoyés sont trop volumineux.',
        'err_cat'      => 'Veuillez choisir une catégorie.',
        'err_title'    => 'Le titre doit contenir entre 3 et 150 caractères.',
        'err_desc'     => 'La description doit contenir entre 10 et 3000 caractères.',
        'err_price'    => 'Prix invalide.',
        'err_city'     => 'Veuillez choisir une ville.',
        'err_phone'    => 'Numéro de téléphone invalide (ex : 0612345678).',
        'err_none'     => 'Ajoutez au moins une photo.',
        'err_too_many' => 'Maximum 5 photos.',
        'err_too_big'  => 'Chaque photo doit faire 2 Mo maximum.',
        'err_bad_type' => 'Format de photo non accepté (JPG, PNG ou WebP uniquement).',
        'err_upload'   => 'Erreur lors de l\'envoi des photos.',
        'err_save'     => 'Impossible d\'enregistrer les photos. Réessayez.',
    ],
    'ar' => [
        'title'        => 'نشر إعلان',
        'st_cat'       => 'الفئة',
        'st_details'   => 'التفاصيل',
        'st_photos'    => 'الصور',
        'st_contact'   => 'التواصل',
        's1_title'     => 'اختر فئة',
        's2_title'     => 'صف إعلانك',
        's3_title'     => 'أضف صورا',
        's4_title'     => 'معلومات الاتصال',
        'f_title'      => 'العنوان',
        'f_desc'       => 'الوصف',
        'f_price'      => 'الثمن (درهم)',
        'f_city'       => 'المدينة',
        'city_choose'  => 'اختر مدينة',
        'photos_hint'  => 'حتى 5 صور (JPG أو PNG أو WebP، بحد أقصى 2 ميغابايت لكل صورة).',
        'draft_note'   => 'تم الاحتفاظ بمعلوماتك. أضف صورك من جديد.',
        'f_phone'      => 'هاتف الاتصال',
        'f_wa'         => 'هذا الرقم متوفر أيضا على واتساب',
        'back'         => 'رجوع',
        'next'         => 'متابعة',
        'preview'      => 'معاينة',
        'cat_required' => 'المرجو اختيار فئة.',
        'js_max'       => 'الحد الأقصى 5 صور.',
        'js_big'       => 'يجب ألا تتجاوز كل صورة 2 ميغابايت.',
        'err_csrf'     => 'انتهت الجلسة. المرجو المحاولة من جديد.',
        'err_size'     => 'الملفات المرسلة كبيرة جدا.',
        'err_cat'      => 'المرجو اختيار فئة.',
        'err_title'    => 'يجب أن يتراوح العنوان بين 3 و150 حرفا.',
        'err_desc'     => 'يجب أن يتراوح الوصف بين 10 و3000 حرف.',
        'err_price'    => 'ثمن غير صالح.',
        'err_city'     => 'المرجو اختيار مدينة.',
        'err_phone'    => 'رقم الهاتف غير صالح (مثال: 0612345678).',
        'err_none'     => 'أضف صورة واحدة على الأقل.',
        'err_too_many' => 'الحد الأقصى 5 صور.',
        'err_too_big'  => 'يجب ألا تتجاوز كل صورة 2 ميغابايت.',
        'err_bad_type' => 'صيغة الصورة غير مقبولة (JPG أو PNG أو WebP فقط).',
        'err_upload'   => 'حدث خطأ أثناء إرسال الصور.',
        'err_save'     => 'تعذر حفظ الصور. أعد المحاولة.',
    ],
];
$tx = $textes_page[$lang];

$categories = $pdo->query('SELECT id, nom_fr, nom_ar, icone FROM categories WHERE actif = 1 ORDER BY id')->fetchAll();
$villes     = $pdo->query('SELECT id, nom_fr, nom_ar FROM villes ORDER BY nom_fr')->fetchAll();
$ids_cat    = array_map('intval', array_column($categories, 'id'));
$ids_villes = array_map('intval', array_column($villes, 'id'));

// Valeurs par defaut du formulaire
$old = [
    'categorie_id' => 0,
    'ville_id'     => (int) ($user['ville_id'] ?? 0),
    'titre'        => '',
    'description'  => '',
    'prix'         => '',
    'telephone'    => $user['telephone'] ?? '',
    'whatsapp'     => 1,
];

$brouillon_existe = !empty($_SESSION['brouillon']);
if ($brouillon_existe) {
    $b = $_SESSION['brouillon'];
    $old = [
        'categorie_id' => (int) $b['categorie_id'],
        'ville_id'     => (int) $b['ville_id'],
        'titre'        => $b['titre'],
        'description'  => $b['description'],
        'prix'         => $b['prix'],
        'telephone'    => $b['telephone'],
        'whatsapp'     => $b['whatsapp'] ? 1 : 0,
    ];
}

$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Si les fichiers depassent la limite du serveur, PHP vide $_POST
    if (empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        $erreurs[] = $tx['err_size'];
    } elseif (!csrf_verifier()) {
        $erreurs[] = $tx['err_csrf'];
    } else {
        $categorie_id = (int) ($_POST['categorie_id'] ?? 0);
        $ville_id     = (int) ($_POST['ville_id'] ?? 0);
        $titre        = trim($_POST['titre'] ?? '');
        $description  = trim($_POST['description'] ?? '');
        $prix_txt     = str_replace([' ', ','], ['', '.'], trim($_POST['prix'] ?? ''));
        $tel          = preg_replace('/[\s.\-]/', '', trim($_POST['telephone'] ?? ''));
        $whatsapp     = isset($_POST['whatsapp']) ? 1 : 0;

        $old = [
            'categorie_id' => $categorie_id,
            'ville_id'     => $ville_id,
            'titre'        => $titre,
            'description'  => $description,
            'prix'         => $prix_txt,
            'telephone'    => $tel,
            'whatsapp'     => $whatsapp,
        ];

        if (!in_array($categorie_id, $ids_cat, true)) {
            $erreurs[] = $tx['err_cat'];
        }
        if (mb_strlen($titre) < 3 || mb_strlen($titre) > 150) {
            $erreurs[] = $tx['err_title'];
        }
        if (mb_strlen($description) < 10 || mb_strlen($description) > 3000) {
            $erreurs[] = $tx['err_desc'];
        }
        if (!is_numeric($prix_txt) || (float) $prix_txt < 0 || (float) $prix_txt > 99999999) {
            $erreurs[] = $tx['err_price'];
        }
        if (!in_array($ville_id, $ids_villes, true)) {
            $erreurs[] = $tx['err_city'];
        }
        if (!preg_match('/^(?:\+212|0)[5-7][0-9]{8}$/', $tel)) {
            $erreurs[] = $tx['err_phone'];
        }

        [$images_ok, $codes_img] = valider_images($_FILES['photos'] ?? []);

        if (!$images_ok && !$codes_img) {
            $erreurs[] = $tx['err_none'];
        }
        foreach ($codes_img as $code) {
            $erreurs[] = $tx['err_' . $code];
        }

        if (!$erreurs) {
            // Supprime l'ancien brouillon (photos temporaires)
            if (!empty($_SESSION['brouillon']['dossier'])) {
                supprimer_dossier(dossier_uploads($_SESSION['brouillon']['dossier']));
            }

            $dossier_nom = 'tmp_' . (int) $user['id'] . '_' . bin2hex(random_bytes(8));
            $noms = [];

            foreach ($images_ok as $img) {
                $nom = enregistrer_image($img['tmp_name'], $img['ext'], dossier_uploads($dossier_nom));
                if ($nom === null) {
                    $erreurs[] = $tx['err_save'];
                    break;
                }
                $noms[] = $nom;
            }

            if ($erreurs) {
                supprimer_dossier(dossier_uploads($dossier_nom));
            } else {
                $_SESSION['brouillon'] = [
                    'categorie_id' => $categorie_id,
                    'ville_id'     => $ville_id,
                    'titre'        => $titre,
                    'description'  => $description,
                    'prix'         => number_format((float) $prix_txt, 2, '.', ''),
                    'telephone'    => $tel,
                    'whatsapp'     => $whatsapp,
                    'dossier'      => $dossier_nom,
                    'images'       => $noms,
                ];
                redirect('pages/apercu.php');
            }
        }
    }
}

$page_title = $tx['title'];
require_once __DIR__ . '/../includes/header.php';

$style_carte = 'background:var(--ac-surface); border:1px solid var(--ac-border); border-radius:var(--ac-radius); box-shadow:var(--ac-shadow);';
?>

<style>
    .btn-check:checked + .ac-cat { border-color: var(--ac-blue); box-shadow: 0 0 0 3px rgba(23, 105, 224, .25); }
    .btn-check:focus-visible + .ac-cat { outline: 2px solid var(--ac-blue); }
    label.ac-cat { cursor: pointer; }
    .pub-bar { height: 4px; border-radius: 2px; background: var(--ac-border); }
    .pub-bar.on { background: var(--ac-blue); }
    .pub-label { font-size: .8rem; color: var(--ac-muted); text-align: center; }
    .pub-label.on { color: var(--ac-blue); font-weight: 700; }
    .pub-thumb { width: 72px; height: 72px; object-fit: cover; border-radius: 8px; border: 1px solid var(--ac-border); }
</style>

<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">

            <h1 class="h3 fw-bold mb-4"><?= e($tx['title']) ?></h1>

            <?= flash_afficher() ?>

            <div class="p-4" style="<?= $style_carte ?>">

                <div class="row g-2 mb-1">
                    <div class="col-3"><div class="pub-bar on"></div></div>
                    <div class="col-3"><div class="pub-bar"></div></div>
                    <div class="col-3"><div class="pub-bar"></div></div>
                    <div class="col-3"><div class="pub-bar"></div></div>
                </div>
                <div class="row g-2 mb-4">
                    <div class="col-3 pub-label on"><?= e($tx['st_cat']) ?></div>
                    <div class="col-3 pub-label"><?= e($tx['st_details']) ?></div>
                    <div class="col-3 pub-label"><?= e($tx['st_photos']) ?></div>
                    <div class="col-3 pub-label"><?= e($tx['st_contact']) ?></div>
                </div>

                <?php if ($erreurs): ?>
                    <div class="alert alert-danger" role="alert">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($erreurs as $erreur): ?>
                                <li><?= e($erreur) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($brouillon_existe && $_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
                    <div class="alert alert-info" role="alert"><?= e($tx['draft_note']) ?></div>
                <?php endif; ?>

                <form method="post" action="" enctype="multipart/form-data" id="formPublier">
                    <?= csrf_field() ?>

                    <!-- Etape 1 : categorie -->
                    <div class="pub-step">
                        <h2 class="h5 fw-bold mb-3"><?= e($tx['s1_title']) ?></h2>
                        <div class="row g-3">
                            <?php foreach ($categories as $c): ?>
                                <div class="col-6 col-md-4">
                                    <input type="radio" class="btn-check" name="categorie_id" id="cat<?= (int) $c['id'] ?>"
                                           value="<?= (int) $c['id'] ?>" <?= (int) $old['categorie_id'] === (int) $c['id'] ? 'checked' : '' ?> required>
                                    <label class="ac-cat" for="cat<?= (int) $c['id'] ?>">
                                        <i class="bi <?= e($c['icone']) ?>"></i>
                                        <?= e($lang === 'ar' ? $c['nom_ar'] : $c['nom_fr']) ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <p class="text-danger small mt-2 d-none" id="msgCat"><?= e($tx['cat_required']) ?></p>
                    </div>

                    <!-- Etape 2 : details -->
                    <div class="pub-step d-none">
                        <h2 class="h5 fw-bold mb-3"><?= e($tx['s2_title']) ?></h2>

                        <div class="mb-3">
                            <label for="titre" class="form-label"><?= e($tx['f_title']) ?></label>
                            <input type="text" class="form-control" id="titre" name="titre" minlength="3" maxlength="150"
                                   value="<?= e($old['titre']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label"><?= e($tx['f_desc']) ?></label>
                            <textarea class="form-control" id="description" name="description" rows="5"
                                      minlength="10" maxlength="3000" required><?= e($old['description']) ?></textarea>
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="prix" class="form-label"><?= e($tx['f_price']) ?></label>
                                <input type="number" class="form-control" id="prix" name="prix" min="0" max="99999999"
                                       step="0.01" value="<?= e($old['prix']) ?>" dir="ltr" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="ville_id" class="form-label"><?= e($tx['f_city']) ?></label>
                                <select class="form-select" id="ville_id" name="ville_id" required>
                                    <option value=""><?= e($tx['city_choose']) ?></option>
                                    <?php foreach ($villes as $v): ?>
                                        <option value="<?= (int) $v['id'] ?>" <?= (int) $old['ville_id'] === (int) $v['id'] ? 'selected' : '' ?>>
                                            <?= e($lang === 'ar' ? $v['nom_ar'] : $v['nom_fr']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Etape 3 : photos -->
                    <div class="pub-step d-none">
                        <h2 class="h5 fw-bold mb-3"><?= e($tx['s3_title']) ?></h2>
                        <input type="file" class="form-control" id="photos" name="photos[]"
                               accept="image/jpeg,image/png,image/webp" multiple required>
                        <p class="text-muted-ac small mt-2"><?= e($tx['photos_hint']) ?></p>
                        <div class="d-flex flex-wrap gap-2 mt-2" id="photosApercu"></div>
                    </div>

                    <!-- Etape 4 : contact -->
                    <div class="pub-step d-none">
                        <h2 class="h5 fw-bold mb-3"><?= e($tx['s4_title']) ?></h2>

                        <div class="mb-3">
                            <label for="telephone" class="form-label"><?= e($tx['f_phone']) ?></label>
                            <input type="tel" class="form-control" id="telephone" name="telephone" maxlength="20"
                                   value="<?= e($old['telephone']) ?>" placeholder="0612345678" dir="ltr" required>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="whatsapp" name="whatsapp" value="1"
                                   <?= $old['whatsapp'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="whatsapp"><?= e($tx['f_wa']) ?></label>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="button" class="btn btn-outline-secondary d-none" id="btnBack"><?= e($tx['back']) ?></button>
                        <button type="button" class="btn btn-ac-orange flex-fill" id="btnNext"><?= e($tx['next']) ?></button>
                        <button type="submit" class="btn btn-ac-blue flex-fill d-none" id="btnSubmit"><?= e($tx['preview']) ?></button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</main>

<script>
(function () {
    var steps   = document.querySelectorAll('.pub-step');
    var bars    = document.querySelectorAll('.pub-bar');
    var labels  = document.querySelectorAll('.pub-label');
    var btnBack = document.getElementById('btnBack');
    var btnNext = document.getElementById('btnNext');
    var btnSubmit = document.getElementById('btnSubmit');
    var msgCat  = document.getElementById('msgCat');
    var photos  = document.getElementById('photos');
    var apercu  = document.getElementById('photosApercu');
    var MSG_MAX = <?= json_encode($tx['js_max'], JSON_UNESCAPED_UNICODE) ?>;
    var MSG_BIG = <?= json_encode($tx['js_big'], JSON_UNESCAPED_UNICODE) ?>;
    var current = 0;

    function afficher(i) {
        current = i;
        steps.forEach(function (s, k) { s.classList.toggle('d-none', k !== i); });
        bars.forEach(function (b, k) { b.classList.toggle('on', k <= i); });
        labels.forEach(function (l, k) { l.classList.toggle('on', k <= i); });
        btnBack.classList.toggle('d-none', i === 0);
        btnNext.classList.toggle('d-none', i === steps.length - 1);
        btnSubmit.classList.toggle('d-none', i !== steps.length - 1);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function etapeValide(i) {
        if (i === 0) {
            var choisi = document.querySelector('input[name="categorie_id"]:checked');
            msgCat.classList.toggle('d-none', !!choisi);
            return !!choisi;
        }
        var champs = steps[i].querySelectorAll('input, select, textarea');
        for (var k = 0; k < champs.length; k++) {
            if (!champs[k].checkValidity()) {
                champs[k].reportValidity();
                return false;
            }
        }
        return true;
    }

    btnNext.addEventListener('click', function () {
        if (etapeValide(current)) { afficher(current + 1); }
    });

    btnBack.addEventListener('click', function () {
        if (current > 0) { afficher(current - 1); }
    });

    photos.addEventListener('change', function () {
        apercu.innerHTML = '';
        var fichiers = photos.files;

        if (fichiers.length > 5) {
            alert(MSG_MAX);
            photos.value = '';
            return;
        }

        for (var i = 0; i < fichiers.length; i++) {
            if (fichiers[i].size > 2 * 1024 * 1024) {
                alert(MSG_BIG);
                photos.value = '';
                apercu.innerHTML = '';
                return;
            }
            var img = document.createElement('img');
            img.className = 'pub-thumb';
            img.alt = '';
            img.src = URL.createObjectURL(fichiers[i]);
            apercu.appendChild(img);
        }
    });
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>