<?php
require_once __DIR__ . '/../includes/lang.php';
require_once __DIR__ . '/../includes/auth.php';

$user = exiger_connexion();
$pdo  = db();

$textes_page = [
    'fr' => [
        'title'       => 'Mon profil',
        'info_title'  => 'Mes informations',
        'name'        => 'Nom complet',
        'email'       => 'Adresse e-mail (non modifiable)',
        'phone'       => 'Téléphone (facultatif)',
        'city'        => 'Ville',
        'city_choose' => 'Choisissez votre ville',
        'save'        => 'Enregistrer',
        'pass_title'  => 'Changer le mot de passe',
        'pass_old'    => 'Mot de passe actuel',
        'pass_new'    => 'Nouveau mot de passe',
        'pass_conf'   => 'Confirmer le nouveau mot de passe',
        'pass_btn'    => 'Changer le mot de passe',
        'err_csrf'    => 'Session expirée. Veuillez réessayer.',
        'err_name'    => 'Le nom doit contenir entre 2 et 100 caractères.',
        'err_phone'   => 'Numéro de téléphone invalide (ex : 0612345678).',
        'err_city'    => 'Veuillez choisir une ville.',
        'err_old'     => 'Le mot de passe actuel est incorrect.',
        'err_pass'    => 'Le nouveau mot de passe doit contenir entre 8 et 72 caractères.',
        'err_confirm' => 'Les mots de passe ne correspondent pas.',
        'ok_info'     => 'Profil mis à jour.',
        'ok_pass'     => 'Mot de passe modifié.',
    ],
    'ar' => [
        'title'       => 'ملفي الشخصي',
        'info_title'  => 'معلوماتي',
        'name'        => 'الاسم الكامل',
        'email'       => 'البريد الإلكتروني (غير قابل للتعديل)',
        'phone'       => 'الهاتف (اختياري)',
        'city'        => 'المدينة',
        'city_choose' => 'اختر مدينتك',
        'save'        => 'حفظ',
        'pass_title'  => 'تغيير كلمة المرور',
        'pass_old'    => 'كلمة المرور الحالية',
        'pass_new'    => 'كلمة المرور الجديدة',
        'pass_conf'   => 'تأكيد كلمة المرور الجديدة',
        'pass_btn'    => 'تغيير كلمة المرور',
        'err_csrf'    => 'انتهت الجلسة. المرجو المحاولة من جديد.',
        'err_name'    => 'يجب أن يتراوح الاسم بين 2 و100 حرف.',
        'err_phone'   => 'رقم الهاتف غير صالح (مثال: 0612345678).',
        'err_city'    => 'المرجو اختيار مدينة.',
        'err_old'     => 'كلمة المرور الحالية غير صحيحة.',
        'err_pass'    => 'يجب أن تتراوح كلمة المرور الجديدة بين 8 و72 حرفا.',
        'err_confirm' => 'كلمتا المرور غير متطابقتين.',
        'ok_info'     => 'تم تحديث الملف الشخصي.',
        'ok_pass'     => 'تم تغيير كلمة المرور.',
    ],
];
$tx = $textes_page[$lang];

$villes = $pdo->query('SELECT id, nom_fr, nom_ar FROM villes ORDER BY nom_fr')->fetchAll();
$ids_villes = array_map('intval', array_column($villes, 'id'));

$stmt = $pdo->prepare('SELECT nom, email, telephone, ville_id, mot_de_passe FROM utilisateurs WHERE id = :id');
$stmt->execute(['id' => $user['id']]);
$me = $stmt->fetch();

$old = [
    'nom'       => $me['nom'],
    'telephone' => $me['telephone'] ?? '',
    'ville_id'  => (int) $me['ville_id'],
];

$erreurs_info = [];
$erreurs_mdp  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    if (!csrf_verifier()) {
        if ($action === 'mot_de_passe') {
            $erreurs_mdp[] = $tx['err_csrf'];
        } else {
            $erreurs_info[] = $tx['err_csrf'];
        }
    } elseif ($action === 'profil') {

        $nom      = trim($_POST['nom'] ?? '');
        $tel      = preg_replace('/[\s.\-]/', '', trim($_POST['telephone'] ?? ''));
        $ville_id = (int) ($_POST['ville_id'] ?? 0);

        $old = ['nom' => $nom, 'telephone' => $tel, 'ville_id' => $ville_id];

        if (mb_strlen($nom) < 2 || mb_strlen($nom) > 100) {
            $erreurs_info[] = $tx['err_name'];
        }
        if ($tel !== '' && !preg_match('/^(?:\+212|0)[5-7][0-9]{8}$/', $tel)) {
            $erreurs_info[] = $tx['err_phone'];
        }
        if (!in_array($ville_id, $ids_villes, true)) {
            $erreurs_info[] = $tx['err_city'];
        }

        if (!$erreurs_info) {
            $maj = $pdo->prepare(
                'UPDATE utilisateurs SET nom = :nom, telephone = :tel, ville_id = :ville WHERE id = :id'
            );
            $maj->execute([
                'nom'   => $nom,
                'tel'   => $tel !== '' ? $tel : null,
                'ville' => $ville_id,
                'id'    => $user['id'],
            ]);

            flash('success', $tx['ok_info']);
            redirect('pages/profil.php');
        }

    } elseif ($action === 'mot_de_passe') {

        $ancien  = $_POST['ancien'] ?? '';
        $nouveau = $_POST['nouveau'] ?? '';
        $conf    = $_POST['confirmation'] ?? '';

        if (!password_verify($ancien, $me['mot_de_passe'])) {
            $erreurs_mdp[] = $tx['err_old'];
        }
        if (strlen($nouveau) < 8 || strlen($nouveau) > 72) {
            $erreurs_mdp[] = $tx['err_pass'];
        } elseif ($nouveau !== $conf) {
            $erreurs_mdp[] = $tx['err_confirm'];
        }

        if (!$erreurs_mdp) {
            $maj = $pdo->prepare('UPDATE utilisateurs SET mot_de_passe = :h WHERE id = :id');
            $maj->execute(['h' => password_hash($nouveau, PASSWORD_DEFAULT), 'id' => $user['id']]);

            session_regenerate_id(true);
            flash('success', $tx['ok_pass']);
            redirect('pages/profil.php');
        }
    }
}

$page_title = $tx['title'];
require_once __DIR__ . '/../includes/header.php';

$style_carte = 'background:var(--ac-surface); border:1px solid var(--ac-border); border-radius:var(--ac-radius); box-shadow:var(--ac-shadow);';
?>

<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-7">

            <h1 class="h3 fw-bold mb-4"><?= e($tx['title']) ?></h1>

            <?= flash_afficher() ?>

            <div class="p-4 mb-4" style="<?= $style_carte ?>">
                <h2 class="h5 fw-bold mb-3"><?= e($tx['info_title']) ?></h2>

                <?php if ($erreurs_info): ?>
                    <div class="alert alert-danger" role="alert">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($erreurs_info as $erreur): ?>
                                <li><?= e($erreur) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" action="" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="profil">

                    <div class="mb-3">
                        <label for="nom" class="form-label"><?= e($tx['name']) ?></label>
                        <input type="text" class="form-control" id="nom" name="nom" maxlength="100"
                               value="<?= e($old['nom']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label"><?= e($tx['email']) ?></label>
                        <input type="email" class="form-control" id="email" value="<?= e($me['email']) ?>" dir="ltr" disabled>
                    </div>

                    <div class="mb-3">
                        <label for="telephone" class="form-label"><?= e($tx['phone']) ?></label>
                        <input type="tel" class="form-control" id="telephone" name="telephone" maxlength="20"
                               value="<?= e($old['telephone']) ?>" dir="ltr">
                    </div>

                    <div class="mb-4">
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

                    <button type="submit" class="btn btn-ac-blue"><?= e($tx['save']) ?></button>
                </form>
            </div>

            <div class="p-4" style="<?= $style_carte ?>">
                <h2 class="h5 fw-bold mb-3"><?= e($tx['pass_title']) ?></h2>

                <?php if ($erreurs_mdp): ?>
                    <div class="alert alert-danger" role="alert">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($erreurs_mdp as $erreur): ?>
                                <li><?= e($erreur) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" action="" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="mot_de_passe">

                    <div class="mb-3">
                        <label for="ancien" class="form-label"><?= e($tx['pass_old']) ?></label>
                        <input type="password" class="form-control" id="ancien" name="ancien"
                               autocomplete="current-password" required>
                    </div>

                    <div class="mb-3">
                        <label for="nouveau" class="form-label"><?= e($tx['pass_new']) ?></label>
                        <input type="password" class="form-control" id="nouveau" name="nouveau"
                               autocomplete="new-password" required>
                    </div>

                    <div class="mb-4">
                        <label for="confirmation" class="form-label"><?= e($tx['pass_conf']) ?></label>
                        <input type="password" class="form-control" id="confirmation" name="confirmation"
                               autocomplete="new-password" required>
                    </div>

                    <button type="submit" class="btn btn-ac-orange"><?= e($tx['pass_btn']) ?></button>
                </form>
            </div>

        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>