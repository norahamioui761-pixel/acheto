<?php
require_once __DIR__ . '/../includes/lang.php';
require_once __DIR__ . '/../includes/auth.php';

$user = exiger_connexion();
$pdo  = db();

$textes_page = [
    'fr' => [
        'title'      => 'Mes messages',
        'empty'      => 'Vous n\'avez pas encore reçu de message.',
        'about'      => 'À propos de :',
        'new'        => 'Nouveau',
        'mark_read'  => 'Marquer comme lu',
        'reply'      => 'Répondre',
        'delete'     => 'Supprimer',
        'confirm_del'=> 'Supprimer ce message ?',
        'err_csrf'   => 'Session expirée. Veuillez réessayer.',
        'ok_del'     => 'Message supprimé.',
        'reply_subj' => 'Re : ',
    ],
    'ar' => [
        'title'      => 'رسائلي',
        'empty'      => 'لم تتوصل بأي رسالة بعد.',
        'about'      => 'بخصوص:',
        'new'        => 'جديد',
        'mark_read'  => 'تعليم كمقروءة',
        'reply'      => 'رد',
        'delete'     => 'حذف',
        'confirm_del'=> 'حذف هذه الرسالة؟',
        'err_csrf'   => 'انتهت الجلسة. المرجو المحاولة من جديد.',
        'ok_del'     => 'تم حذف الرسالة.',
        'reply_subj' => 'Re : ',
    ],
];
$tx = $textes_page[$lang];

// Actions (uniquement sur les messages des annonces de l'utilisateur)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_verifier()) {
        flash('danger', $tx['err_csrf']);
    } else {
        $action = $_POST['action'] ?? '';
        $id     = (int) ($_POST['id'] ?? 0);

        if ($action === 'lu') {
            $maj = $pdo->prepare(
                'UPDATE messages m
                 JOIN annonces a ON a.id = m.annonce_id
                 SET m.lu = 1
                 WHERE m.id = :id AND a.utilisateur_id = :u'
            );
            $maj->execute(['id' => $id, 'u' => $user['id']]);

        } elseif ($action === 'supprimer') {
            $del = $pdo->prepare(
                'DELETE m FROM messages m
                 JOIN annonces a ON a.id = m.annonce_id
                 WHERE m.id = :id AND a.utilisateur_id = :u'
            );
            $del->execute(['id' => $id, 'u' => $user['id']]);
            flash('success', $tx['ok_del']);
        }
    }

    redirect('pages/messages.php');
}

$stmt = $pdo->prepare(
    'SELECT m.id, m.nom_expediteur, m.email_expediteur, m.contenu, m.lu, m.date_envoi,
            a.id AS annonce_id, a.titre
     FROM messages m
     JOIN annonces a ON a.id = m.annonce_id
     WHERE a.utilisateur_id = :u
     ORDER BY m.date_envoi DESC
     LIMIT 100'
);
$stmt->execute(['u' => $user['id']]);
$messages = $stmt->fetchAll();

$page_title = $tx['title'];
require_once __DIR__ . '/../includes/header.php';

$style_carte = 'background:var(--ac-surface); border:1px solid var(--ac-border); border-radius:var(--ac-radius); box-shadow:var(--ac-shadow);';
?>

<main class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">

            <h1 class="h3 fw-bold mb-4"><?= e($tx['title']) ?></h1>

            <?= flash_afficher() ?>

            <?php if (!$messages): ?>
                <div class="p-4 text-center text-muted-ac" style="<?= $style_carte ?>"><?= e($tx['empty']) ?></div>
            <?php endif; ?>

            <?php foreach ($messages as $m): ?>
                <div class="p-3 mb-3" style="<?= $style_carte ?> <?= $m['lu'] ? '' : 'border-color:var(--ac-blue);' ?>">

                    <div class="d-flex flex-wrap justify-content-between gap-2 mb-1">
                        <div>
                            <strong><?= e($m['nom_expediteur']) ?></strong>
                            <span class="text-muted-ac small" dir="ltr">&lt;<?= e($m['email_expediteur']) ?>&gt;</span>
                            <?php if (!$m['lu']): ?>
                                <span class="badge text-bg-primary"><?= e($tx['new']) ?></span>
                            <?php endif; ?>
                        </div>
                        <span class="text-muted-ac small"><?= e(date('d/m/Y H:i', strtotime($m['date_envoi']))) ?></span>
                    </div>

                    <p class="small text-muted-ac mb-2">
                        <?= e($tx['about']) ?>
                        <a href="<?= BASE_URL ?>pages/details.php?id=<?= (int) $m['annonce_id'] ?>"><?= e($m['titre']) ?></a>
                    </p>

                    <p style="white-space: pre-line;"><?= e($m['contenu']) ?></p>

                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn btn-sm btn-ac-blue"
                           href="mailto:<?= e($m['email_expediteur']) ?>?subject=<?= e(rawurlencode($tx['reply_subj'] . $m['titre'])) ?>">
                            <i class="bi bi-reply me-1"></i><?= e($tx['reply']) ?>
                        </a>

                        <?php if (!$m['lu']): ?>
                            <form method="post" action="" class="m-0">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="lu">
                                <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-check2 me-1"></i><?= e($tx['mark_read']) ?>
                                </button>
                            </form>
                        <?php endif; ?>

                        <form method="post" action="" class="m-0"
                              onsubmit="return confirm(<?= e(json_encode($tx['confirm_del'], JSON_UNESCAPED_UNICODE)) ?>);">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="supprimer">
                            <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash me-1"></i><?= e($tx['delete']) ?>
                            </button>
                        </form>
                    </div>

                </div>
            <?php endforeach; ?>

        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>