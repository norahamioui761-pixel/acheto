<?php
require_once __DIR__ . '/../includes/lang.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cartes.php';

$pdo  = db();
$user = utilisateur_courant();

$textes_page = [
    'fr' => [
        'title'      => 'Envoyer un message',
        'about'      => 'À propos de l\'annonce',
        'name'       => 'Votre nom',
        'email'      => 'Votre adresse e-mail',
        'message'    => 'Votre message',
        'send'       => 'Envoyer le message',
        'back'       => 'Retour à l\'annonce',
        'not_found'  => 'Cette annonce est introuvable ou n\'est plus disponible.',
        'own'        => 'Vous ne pouvez pas envoyer un message pour votre propre annonce.',
        'err_csrf'   => 'Session expirée. Veuillez réessayer.',
        'err_name'   => 'Le nom doit contenir entre 2 et 100 caractères.',
        'err_email'  => 'Adresse e-mail invalide.',
        'err_msg'    => 'Le message doit contenir entre 10 et 2000 caractères.',
        'err_limit'  => 'Trop de messages envoyés. Réessayez plus tard.',
        'err_server' => 'Une erreur est survenue. Veuillez réessayer.',
        'ok'         => 'Votre message a été envoyé au vendeur.',
    ],
    'ar' => [
        'title'      => 'إرسال رسالة',
        'about'      => 'بخصوص الإعلان',
        'name'       => 'اسمك',
        'email'      => 'بريدك الإلكتروني',
        'message'    => 'رسالتك',
        'send'       => 'إرسال الرسالة',
        'back'       => 'العودة إلى الإعلان',
        'not_found'  => 'هذا الإعلان غير موجود أو لم يعد متاحا.',
        'own'        => 'لا يمكنك إرسال رسالة بخصوص إعلانك الخاص.',
        'err_csrf'   => 'انتهت الجلسة. المرجو المحاولة من جديد.',
        'err_name'   => 'يجب أن يتراوح الاسم بين 2 و100 حرف.',
        'err_email'  => 'البريد الإلكتروني غير صالح.',
        'err_msg'    => 'يجب أن تتراوح الرسالة بين 10 و2000 حرف.',
        'err_limit'  => 'عدد كبير من الرسائل. أعد المحاولة لاحقا.',
        'err_server' => 'حدث خطأ. المرجو المحاولة من جديد.',
        'ok'         => 'تم إرسال رسالتك إلى البائع.',
    ],
];
$tx = $textes_page[$lang];

$id = is_string($_GET['id'] ?? null) ? (int) $_GET['id'] : (int) ($_POST['id'] ?? 0);

$stmt = $pdo->prepare(
    "SELECT a.id, a.titre, a.prix, a.utilisateur_id,
            (SELECT i.chemin FROM images_annonces i
              WHERE i.annonce_id = a.id
              ORDER BY i.est_principale DESC, i.ordre ASC LIMIT 1) AS image
     FROM annonces a
     WHERE a.id = :id AND a.statut = 'approuvee' AND a.date_expiration > NOW()"
);
$stmt->execute(['id' => $id]);
$a = $stmt->fetch();

$page_title = $tx['title'];

if (!$a) {
    http_response_code(404);
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

$est_proprietaire = $user && (int) $user['id'] === (int) $a['utilisateur_id'];

$erreurs = [];
$old = [
    'nom'     => $user['nom'] ?? '',
    'email'   => $user['email'] ?? '',
    'message' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$est_proprietaire) {

    if (!csrf_verifier()) {
        $erreurs[] = $tx['err_csrf'];
    } else {
        $nom     = trim($_POST['nom'] ?? '');
        $email   = strtolower(trim($_POST['email'] ?? ''));
        $contenu = trim($_POST['message'] ?? '');
        $piege   = trim($_POST['site_web'] ?? ''); // champ cache : un robot le remplit

        $old = ['nom' => $nom, 'email' => $email, 'message' => $contenu];

        // Limite : 5 messages par heure et par session
        $recents = array_filter($_SESSION['messages_envoyes'] ?? [], function ($t) {
            return $t > time() - 3600;
        });

        if (mb_strlen($nom) < 2 || mb_strlen($nom) > 100) {
            $erreurs[] = $tx['err_name'];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
            $erreurs[] = $tx['err_email'];
        }
        if (mb_strlen($contenu) < 10 || mb_strlen($contenu) > 2000) {
            $erreurs[] = $tx['err_msg'];
        }
        if (count($recents) >= 5) {
            $erreurs[] = $tx['err_limit'];
        }

        if (!$erreurs) {
            if ($piege !== '') {
                // Robot : on fait comme si tout allait bien, sans rien enregistrer
                flash('success', $tx['ok']);
                redirect('pages/details.php?id=' . $id);
            }

            try {
                $ins = $pdo->prepare(
                    'INSERT INTO messages (annonce_id, expediteur_id, nom_expediteur, email_expediteur, contenu)
                     VALUES (:a, :u, :nom, :email, :contenu)'
                );
                $ins->execute([
                    'a'       => $id,
                    'u'       => $user ? $user['id'] : null,
                    'nom'     => $nom,
                    'email'   => $email,
                    'contenu' => $contenu,
                ]);

                $recents[] = time();
                $_SESSION['messages_envoyes'] = array_values($recents);

                flash('success', $tx['ok']);
                redirect('pages/details.php?id=' . $id);
            } catch (PDOException $ex) {
                error_log('Acheto - message : ' . $ex->getMessage());
                $erreurs[] = $tx['err_server'];
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';

$style_carte = 'background:var(--ac-surface); border:1px solid var(--ac-border); border-radius:var(--ac-radius); box-shadow:var(--ac-shadow);';
?>

<main class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-md-9 col-lg-6">

            <a href="<?= BASE_URL ?>pages/details.php?id=<?= (int) $a['id'] ?>" class="text-decoration-none small">
                <i class="bi bi-arrow-<?= $dir === 'rtl' ? 'right' : 'left' ?>"></i> <?= e($tx['back']) ?>
            </a>

            <h1 class="h3 fw-bold my-3"><?= e($tx['title']) ?></h1>

            <div class="p-3 mb-3 d-flex gap-3 align-items-center" style="<?= $style_carte ?>">
                <?php if ($a['image']): ?>
                    <img src="<?= e(BASE_URL . 'uploads/annonces/' . $a['image']) ?>" alt=""
                         style="width:72px; height:72px; object-fit:cover; border-radius:8px;">
                <?php endif; ?>
                <div>
                    <div class="text-muted-ac small"><?= e($tx['about']) ?></div>
                    <div class="fw-bold"><?= e($a['titre']) ?></div>
                    <div class="ac-price"><?= e(formater_prix($a['prix'], $lang)) ?></div>
                </div>
            </div>

            <?php if ($est_proprietaire): ?>
                <div class="alert alert-info" role="alert"><?= e($tx['own']) ?></div>
            <?php else: ?>

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
                        <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">

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

                        <div class="mb-4">
                            <label for="message" class="form-label"><?= e($tx['message']) ?></label>
                            <textarea class="form-control" id="message" name="message" rows="5"
                                      minlength="10" maxlength="2000" required><?= e($old['message']) ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-ac-blue w-100"><?= e($tx['send']) ?></button>
                    </form>
                </div>

            <?php endif; ?>

        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>