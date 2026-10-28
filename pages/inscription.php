<?php
require_once __DIR__ . '/../includes/lang.php';
require_once __DIR__ . '/../includes/auth.php';

if (est_connecte()) {
    redirect('index.php');
}

$textes_page = [
    'fr' => [
        'title'        => 'Inscription',
        'tab_login'    => 'Connexion',
        'tab_register' => 'Inscription',
        'name'         => 'Nom complet',
        'email'        => 'Adresse e-mail',
        'phone'        => 'Téléphone (facultatif)',
        'city'         => 'Ville',
        'city_choose'  => 'Choisissez votre ville',
        'password'     => 'Mot de passe',
        'confirm'      => 'Confirmer le mot de passe',
        'submit'       => 'Créer mon compte',
        'err_csrf'     => 'Session expirée. Veuillez réessayer.',
        'err_name'     => 'Le nom doit contenir entre 2 et 100 caractères.',
        'err_email'    => 'Adresse e-mail invalide.',
        'err_phone'    => 'Numéro de téléphone invalide (ex : 0612345678).',
        'err_city'     => 'Veuillez choisir une ville.',
        'err_pass'     => 'Le mot de passe doit contenir entre 8 et 72 caractères.',
        'err_confirm'  => 'Les mots de passe ne correspondent pas.',
        'err_exists'   => 'Cette adresse e-mail est déjà utilisée.',
        'err_server'   => 'Une erreur est survenue. Veuillez réessayer.',
        'ok'           => 'Compte créé avec succès. Vous pouvez vous connecter.',
    ],
    'ar' => [
        'title'        => 'التسجيل',
        'tab_login'    => 'تسجيل الدخول',
        'tab_register' => 'إنشاء حساب',
        'name'         => 'الاسم الكامل',
        'email'        => 'البريد الإلكتروني',
        'phone'        => 'الهاتف (اختياري)',
        'city'         => 'المدينة',
        'city_choose'  => 'اختر مدينتك',
        'password'     => 'كلمة المرور',
        'confirm'      => 'تأكيد كلمة المرور',
        'submit'       => 'إنشاء الحساب',
        'err_csrf'     => 'انتهت الجلسة. المرجو المحاولة من جديد.',
        'err_name'     => 'يجب أن يتراوح الاسم بين 2 و100 حرف.',
        'err_email'    => 'البريد الإلكتروني غير صالح.',
        'err_phone'    => 'رقم الهاتف غير صالح (مثال: 0612345678).',
        'err_city'     => 'المرجو اختيار مدينة.',
        'err_pass'     => 'يجب أن تتراوح كلمة المرور بين 8 و72 حرفا.',
        'err_confirm'  => 'كلمتا المرور غير متطابقتين.',
        'err_exists'   => 'هذا البريد الإلكتروني مستعمل من قبل.',
        'err_server'   => 'حدث خطأ. المرجو المحاولة من جديد.',
        'ok'           => 'تم إنشاء الحساب بنجاح. يمكنك الآن تسجيل الدخول.',
    ],
];
$tx = $textes_page[$lang];

$pdo = db();
$villes = $pdo->query('SELECT id, nom_fr, nom_ar FROM villes ORDER BY nom_fr')->fetchAll();
$ids_villes = array_map('intval', array_column($villes, 'id'));

$erreurs = [];
$old = ['nom' => '', 'email' => '', 'telephone' => '', 'ville_id' => 0];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_verifier()) {
        $erreurs[] = $tx['err_csrf'];
    } else {
        $nom      = trim($_POST['nom'] ?? '');
        $email    = strtolower(trim($_POST['email'] ?? ''));
        $tel      = preg_replace('/[\s.\-]/', '', trim($_POST['telephone'] ?? ''));
        $ville_id = (int) ($_POST['ville_id'] ?? 0);
        $mdp      = $_POST['mot_de_passe'] ?? '';
        $conf     = $_POST['confirmation'] ?? '';

        $old = ['nom' => $nom, 'email' => $email, 'telephone' => $tel, 'ville_id' => $ville_id];

        if (mb_strlen($nom) < 2 || mb_strlen($nom) > 100) {
            $erreurs[] = $tx['err_name'];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
            $erreurs[] = $tx['err_email'];
        }
        if ($tel !== '' && !preg_match('/^(?:\+212|0)[5-7][0-9]{8}$/', $tel)) {
            $erreurs[] = $tx['err_phone'];
        }
        if (!in_array($ville_id, $ids_villes, true)) {
            $erreurs[] = $tx['err_city'];
        }
        if (strlen($mdp) < 8 || strlen($mdp) > 72) {
            $erreurs[] = $tx['err_pass'];
        } elseif ($mdp !== $conf) {
            $erreurs[] = $tx['err_confirm'];
        }

        if (!$erreurs) {
            $stmt = $pdo->prepare('SELECT id FROM utilisateurs WHERE email = :email');
            $stmt->execute(['email' => $email]);

            if ($stmt->fetch()) {
                $erreurs[] = $tx['err_exists'];
            } else {
                try {
                    $stmt = $pdo->prepare(
                        'INSERT INTO utilisateurs (nom, email, telephone, ville_id, mot_de_passe, role)
                         VALUES (:nom, :email, :tel, :ville, :mdp, \'membre\')'
                    );
                    $stmt->execute([
                        'nom'   => $nom,
                        'email' => $email,
                        'tel'   => $tel !== '' ? $tel : null,
                        'ville' => $ville_id,
                        'mdp'   => password_hash($mdp, PASSWORD_DEFAULT),
                    ]);

                    flash('success', $tx['ok']);
                    redirect('pages/connexion.php');
                } catch (PDOException $ex) {
                    if ($ex->getCode() === '23000') {
                        $erreurs[] = $tx['err_exists'];
                    } else {
                        error_log('Acheto - inscription : ' . $ex->getMessage());
                        $erreurs[] = $tx['err_server'];
                    }
                }
            }
        }
    }
}

$page_title = $tx['title'];
require_once __DIR__ . '/../includes/header.php';
?>

<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-5">

            <div class="text-center mb-4">
                <img src="<?= BASE_URL ?>assets/logo.jpeg" alt="Acheto" style="height:70px; width:auto;">
            </div>

            <div class="p-4" style="background:var(--ac-surface); border:1px solid var(--ac-border); border-radius:var(--ac-radius); box-shadow:var(--ac-shadow);">

                <div class="d-flex gap-1 p-1 mb-4" style="background:var(--ac-bg); border-radius:10px;">
                    <a href="<?= BASE_URL ?>pages/connexion.php" class="flex-fill text-center text-decoration-none py-2 fw-semibold"
                       style="border-radius:8px; color:var(--ac-muted);"><?= e($tx['tab_login']) ?></a>
                    <span class="flex-fill text-center py-2 fw-semibold"
                          style="border-radius:8px; background:#1769E0; color:#fff;"><?= e($tx['tab_register']) ?></span>
                </div>

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

                <form method="post" action="" novalidate>
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="nom" class="form-label"><?= e($tx['name']) ?></label>
                        <input type="text" class="form-control" id="nom" name="nom" maxlength="100"
                               value="<?= e($old['nom']) ?>" autocomplete="name" required>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label"><?= e($tx['email']) ?></label>
                        <input type="email" class="form-control" id="email" name="email" maxlength="150"
                               value="<?= e($old['email']) ?>" autocomplete="email" required>
                    </div>

                    <div class="mb-3">
                        <label for="telephone" class="form-label"><?= e($tx['phone']) ?></label>
                        <input type="tel" class="form-control" id="telephone" name="telephone" maxlength="20"
                               value="<?= e($old['telephone']) ?>" autocomplete="tel" dir="ltr">
                    </div>

                    <div class="mb-3">
                        <label for="ville_id" class="form-label"><?= e($tx['city']) ?></label>
                        <select class="form-select" id="ville_id" name="ville_id" required>
                            <option value=""><?= e($tx['city_choose']) ?></option>
                            <?php foreach ($villes as $v): ?>
                                <option value="<?= (int) $v['id'] ?>" <?= (int) $old['ville_id'] === (int) $v['id'] ? 'selected' : '' ?>>
                                    <?= e($lang === 'ar' ? $v['nom_ar'] : $v['nom_fr']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="mot_de_passe" class="form-label"><?= e($tx['password']) ?></label>
                        <input type="password" class="form-control" id="mot_de_passe" name="mot_de_passe"
                               autocomplete="new-password" required>
                    </div>

                    <div class="mb-4">
                        <label for="confirmation" class="form-label"><?= e($tx['confirm']) ?></label>
                        <input type="password" class="form-control" id="confirmation" name="confirmation"
                               autocomplete="new-password" required>
                    </div>

                    <button type="submit" class="btn btn-ac-blue w-100"><?= e($tx['submit']) ?></button>
                </form>
            </div>

        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>