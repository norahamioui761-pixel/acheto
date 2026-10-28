<?php
require_once __DIR__ . '/../includes/lang.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin-textes.php';

$admin = exiger_admin();
$pdo   = db();

$retour = 'admin/contacts.php';

// Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_verifier()) {
        flash('danger', ta_brut('err_csrf'));
        redirect($retour);
    }

    $action = $_POST['action'] ?? '';
    $id     = (int) ($_POST['id'] ?? 0);

    if ($action === 'lu') {
        $maj = $pdo->prepare('UPDATE contacts SET lu = 1 WHERE id = :id');
        $maj->execute(['id' => $id]);

    } elseif ($action === 'supprimer') {
        $del = $pdo->prepare('DELETE FROM contacts WHERE id = :id');
        $del->execute(['id' => $id]);
        flash('success', ta_brut('msg_deleted'));
    }

    redirect($retour);
}

$messages = $pdo->query(
    'SELECT id, nom, email, sujet, message, lu, date_envoi
     FROM contacts
     ORDER BY lu ASC, date_envoi DESC
     LIMIT 100'
)->fetchAll();

$page_title = ta_brut('msgs_title');
require_once __DIR__ . '/../includes/header.php';

$style_carte = 'background:var(--ac-surface); border:1px solid var(--ac-border); border-radius:var(--ac-radius); box-shadow:var(--ac-shadow);';
?>

<main class="container py-5">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h3 fw-bold mb-0"><?= ta('msgs_title') ?></h1>
        <a class="btn btn-outline-secondary btn-sm" href="<?= BASE_URL ?>admin/index.php"><?= ta('dashboard') ?></a>
    </div>

    <?= flash_afficher() ?>

    <?php if (!$messages): ?>
        <div class="p-4 text-center text-muted-ac" style="<?= $style_carte ?>"><?= ta('no_messages') ?></div>
    <?php endif; ?>

    <?php foreach ($messages as $m): ?>
        <div class="p-3 mb-3" style="<?= $style_carte ?> <?= $m['lu'] ? '' : 'border-color:var(--ac-blue);' ?>">

            <div class="d-flex flex-wrap justify-content-between gap-2 mb-1">
                <div>
                    <strong><?= e($m['nom']) ?></strong>
                    <span class="text-muted-ac small" dir="ltr">&lt;<?= e($m['email']) ?>&gt;</span>
                    <?php if (!$m['lu']): ?>
                        <span class="badge text-bg-primary"><?= ta('new') ?></span>
                    <?php endif; ?>
                </div>
                <span class="text-muted-ac small" dir="ltr"><?= e(date('d/m/Y H:i', strtotime($m['date_envoi']))) ?></span>
            </div>

            <p class="fw-semibold mb-1"><?= e($m['sujet']) ?></p>
            <p style="white-space: pre-line;"><?= e($m['message']) ?></p>

            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-sm btn-ac-blue"
                   href="mailto:<?= e($m['email']) ?>?subject=<?= e(rawurlencode('Re : ' . $m['sujet'])) ?>">
                    <i class="bi bi-reply me-1"></i><?= ta('reply') ?>
                </a>

                <?php if (!$m['lu']): ?>
                    <form method="post" action="" class="m-0">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="lu">
                        <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-check2 me-1"></i><?= ta('mark_read') ?>
                        </button>
                    </form>
                <?php endif; ?>

                <form method="post" action="" class="m-0"
                      onsubmit="return confirm(<?= e(json_encode(ta_brut('confirm_del_msg'), JSON_UNESCAPED_UNICODE)) ?>);">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="supprimer">
                    <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger">
                        <i class="bi bi-trash me-1"></i><?= ta('delete') ?>
                    </button>
                </form>
            </div>

        </div>
    <?php endforeach; ?>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>