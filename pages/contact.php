<?php
require_once __DIR__ . '/../includes/lang.php';
require_once __DIR__ . '/../includes/auth.php';

$pdo  = db();
$user = utilisateur_courant();

$textes_page = [
    'fr' => [
        'title'      => 'Contact',
        'intro'      => 'Une question, une suggestion ou un problème ? Écrivez-nous.',
        'name'       => 'Votre nom',
        'email'      => 'Votre adresse e-mail',
        'subject'    => 'Sujet',
        'message'    => 'Votre message',
        'send'       => 'Envoyer',
        'err_csrf'   => 'Session expirée. Veuillez réessayer.',
        'err_name'   => 'Le nom doit contenir entre 2 et 100 caractères.',
        'err_email'  => 'Adresse e-mail invalide.',
        'err_subject'=> 'Le sujet doit contenir entre 3 et 150 caractères.',
        'err_msg'    => 'Le message doit contenir entre 10 et 3000 caractères.',
        'err_limit'  => 'Trop de messages envoyés. Réessayez plus tard.',
        'err_server' => 'Une erreur est survenue. Veuillez réessayer.',
        'ok'         => 'Votre message a bien été envoyé. Merci !',
    ],
    'ar' => [
        'title'      => 'اتصل بنا',
        'intro'      => 'سؤال أو اقتراح أو مشكل؟ راسلنا.',
        'name'       => 'اسمك',
        'email'      => 'بريدك الإلكتروني',
        'subject'    => 'الموضوع',
        'message'    => 'رسالتك',
        'send'       => 'إرسال',
        'err_csrf'   => 'انتهت الجلسة. المرجو المحاولة من جديد.',
        'err_name'   => 'يجب أن يتراوح الاسم بين 2 و100 حرف.',
        'err_email'  => 'البريد الإلكتروني غير صالح.',
        'err_subject'=> 'يجب أن يتراوح الموضوع بين 3 و150 حرفا.',
        'err_msg'    => 'يجب أن تتراوح الرسالة بين 10 و3000 حرف.',
        'err_limit'  => 'عدد كبير من الرسائل. أعد المحاولة لاحقا.',
        'err_server' => 'حدث خطأ. المرجو المحاولة من جديد.',
        'ok'         => 'تم إرسال رسالتك بنجاح. شكرا لك!',
    ],
];
$tx = $textes_page[$lang];

$erreurs = [];
$old = [
    'nom'     => $user['nom'] ?? '',
    'email'   => $user['email'] ?? '',
    'sujet'   => '',
    'message' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_verifier()) {
        $erreurs[] = $tx['err_csrf'];
    } else {
        $nom     = trim($_POST['nom'] ?? '');
        $email   = strtolower(trim($_POST['email'] ?? ''));
        $sujet   = trim($_POST['sujet'] ?? '');
        $contenu = trim($_POST['message'] ?? '');
        $piege   = trim($_POST['site_web'] ?? ''); // champ cache : un robot le remplit

        $old = ['nom' => $nom, 'email' => $email, 'sujet' => $sujet, 'message' => $contenu];

        // Limite : 3 messages par heure et par session
        $recents = array_filter($_SESSION['contacts_envoyes'] ?? [], function ($t) {
            return $t > time() - 3600;
        });

        if (mb_strlen($nom) < 2 || mb_strlen($nom) > 100) {
            $erreurs[] = $tx['err_name'];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
            $erreurs[] = $tx['err_email'];
        }
        if (mb_strlen($sujet) < 3 || mb_strlen($sujet) > 150) {
            $erreurs[] = $tx['err_subject'];
        }
        if (mb_strlen($contenu) < 10 || mb_strlen($contenu) > 3000) {
            $erreurs[] = $tx['err_msg'];
        }
        if (count($recents) >= 3) {
            $erreurs[] = $tx['err_limit'];
        }

        if (!$erreurs) {
            if ($piege !== '') {
                // Robot : on fait comme si tout allait bien, sans rien enregistrer
                flash('success', $tx['ok']);
                redirect('pages/contact.php');
            }

            try {
                $ins = $pdo->prepare(
                    'INSERT INTO contacts (nom, email, sujet, message) VALUES (:nom, :email, :sujet, :message)'
                );
                $ins->execute([
                    'nom'     => $nom,
                    'email'   => $email,
                    'sujet'   => $sujet,
                    'message' => $contenu,
                ]);

                $recents[] = time();
                $_SESSION['contacts_envoyes'] = array_values($recents);

                flash('success', $tx['ok']);
                redirect('pages/contact.php');
            } catch (PDOException $ex) {
                error_log('Acheto - contact : ' . $ex->getMessage());
                $erreurs[] = $tx['err_server'];
            }
        }
    }
}

$page_title = $tx['title'];
require_once __DIR__ . '/../includes/header.php';

$style_carte = 'background:var(--ac-surface); border:1px solid var(--ac-border); border-radius:var(--ac-radius); box-shadow:var(--ac-shadow);';
?>

<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-9 col-lg-6">

            <h1 class="h3 fw-bold mb-2"><?= e($tx['title']) ?></h1>
            <p class="text-muted-ac mb-4"><?= e($tx['intro']) ?></p>

            <?= flash_afficher() ?>

            <div class="p-4" style="<?= $style_carte ?>">

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

                    <div style="position:absolute; left:-9999px;" aria-hidden="true">
                        <label>Ne pas remplir <input type="text" name="site_web" tabindex="-1" autocomplete="off"></label>
                    </div>

                    <div class="mb-3">
                        <label for="nom" class="form-label"><?= e($tx['name']) ?></label>
                        <input type="text" class="form-control" id="nom" name="nom" maxlength="100"
                               value="<?= e($old['nom']) ?>" autocomplete="name" required>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label"><?= e($tx['email']) ?></label>
                        <input type="email" class="form-control" id="email" name="email" maxlength="150"
                               value="<?= e($old['email']) ?>" autocomplete="email" dir="ltr" required>
                    </div>

                    <div class="mb-3">
                        <label for="sujet" class="form-label"><?= e($tx['subject']) ?></label>
                        <input type="text" class="form-control" id="sujet" name="sujet" maxlength="150"
                               value="<?= e($old['sujet']) ?>" required>
                    </div>

                    <div class="mb-4">
                        <label for="message" class="form-label"><?= e($tx['message']) ?></label>
                        <textarea class="form-control" id="message" name="message" rows="6"
                                  minlength="10" maxlength="3000" required><?= e($old['message']) ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-ac-blue w-100"><?= e($tx['send']) ?></button>
                </form>
            </div>

        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>