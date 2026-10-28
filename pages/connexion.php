<?php
require_once __DIR__ . '/../includes/lang.php';
require_once __DIR__ . '/../includes/auth.php';

if (est_connecte()) {
    redirect('index.php');
}

$textes_page = [
    'fr' => [
        'title'        => 'Connexion',
        'tab_login'    => 'Connexion',
        'tab_register' => 'Inscription',
        'email'        => 'Adresse e-mail',
        'password'     => 'Mot de passe',
        'submit'       => 'Se connecter',
        'no_account'   => 'Pas encore de compte ?',
        'create'       => 'Créer un compte',
        'err_csrf'     => 'Session expirée. Veuillez réessayer.',
        'err_login'    => 'E-mail ou mot de passe incorrect.',
        'err_disabled' => 'Ce compte est désactivé.',
        'err_blocked'  => 'Trop de tentatives. Réessayez dans une minute.',
    ],
    'ar' => [
        'title'        => 'تسجيل الدخول',
        'tab_login'    => 'تسجيل الدخول',
        'tab_register' => 'إنشاء حساب',
        'email'        => 'البريد الإلكتروني',
        'password'     => 'كلمة المرور',
        'submit'       => 'دخول',
        'no_account'   => 'ليس لديك حساب؟',
        'create'       => 'إنشاء حساب',
        'err_csrf'     => 'انتهت الجلسة. المرجو المحاولة من جديد.',
        'err_login'    => 'البريد الإلكتروني أو كلمة المرور غير صحيحة.',
        'err_disabled' => 'هذا الحساب معطل.',
        'err_blocked'  => 'محاولات كثيرة. أعد المحاولة بعد دقيقة.',
    ],
];
$tx = $textes_page[$lang];

$erreurs = [];
$email_old = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_verifier()) {
        $erreurs[] = $tx['err_csrf'];
    } elseif (!empty($_SESSION['login_bloque_jusqua']) && time() < $_SESSION['login_bloque_jusqua']) {
        $erreurs[] = $tx['err_blocked'];
    } else {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $mdp   = $_POST['mot_de_passe'] ?? '';
        $email_old = $email;

        $stmt = db()->prepare('SELECT id, mot_de_passe, actif FROM utilisateurs WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $u = $stmt->fetch();

        // Meme duree de calcul que l'utilisateur existe ou non
        $hash = $u ? $u['mot_de_passe'] : password_hash('inexistant', PASSWORD_DEFAULT);
        $mdp_ok = password_verify($mdp, $hash);

        if ($u && $mdp_ok) {
            if ((int) $u['actif'] !== 1) {
                $erreurs[] = $tx['err_disabled'];
            } else {
                if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
                    $maj = db()->prepare('UPDATE utilisateurs SET mot_de_passe = :h WHERE id = :id');
                    $maj->execute(['h' => password_hash($mdp, PASSWORD_DEFAULT), 'id' => $u['id']]);
                }

                unset($_SESSION['login_echecs'], $_SESSION['login_bloque_jusqua']);
                connecter_utilisateur($u);
                redirect('index.php');
            }
        } else {
            $_SESSION['login_echecs'] = ($_SESSION['login_echecs'] ?? 0) + 1;

            if ($_SESSION['login_echecs'] >= 5) {
                $_SESSION['login_bloque_jusqua'] = time() + 60;
                $_SESSION['login_echecs'] = 0;
            }

            $erreurs[] = $tx['err_login'];
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
                    <span class="flex-fill text-center py-2 fw-semibold"
                          style="border-radius:8px; background:#1769E0; color:#fff;"><?= e($tx['tab_login']) ?></span>
                    <a href="<?= BASE_URL ?>pages/inscription.php" class="flex-fill text-center text-decoration-none py-2 fw-semibold"
                       style="border-radius:8px; color:var(--ac-muted);"><?= e($tx['tab_register']) ?></a>
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
                        <label for="email" class="form-label"><?= e($tx['email']) ?></label>
                        <input type="email" class="form-control" id="email" name="email" maxlength="150"
                               value="<?= e($email_old) ?>" autocomplete="email" required>
                    </div>

                    <div class="mb-4">
                        <label for="mot_de_passe" class="form-label"><?= e($tx['password']) ?></label>
                        <input type="password" class="form-control" id="mot_de_passe" name="mot_de_passe"
                               autocomplete="current-password" required>
                    </div>

                    <button type="submit" class="btn btn-ac-blue w-100"><?= e($tx['submit']) ?></button>
                </form>

                <p class="text-center small mt-3 mb-0 text-muted-ac">
                    <?= e($tx['no_account']) ?>
                    <a href="<?= BASE_URL ?>pages/inscription.php" class="fw-semibold" style="color:var(--ac-orange); text-decoration:none;"><?= e($tx['create']) ?></a>
                </p>
            </div>

        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>