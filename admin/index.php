<?php
require_once __DIR__ . '/../includes/lang.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cartes.php';
require_once __DIR__ . '/../includes/admin-textes.php';

$admin = exiger_admin();
$pdo   = db();

// Les annonces dont la date d'expiration est passee passent au statut "expiree"
$pdo->exec("UPDATE annonces SET statut = 'expiree' WHERE statut = 'approuvee' AND date_expiration <= NOW()");

function compter(PDO $pdo, string $sql): int
{
    return (int) $pdo->query($sql)->fetchColumn();
}

$stats = [
    'en_attente'   => compter($pdo, "SELECT COUNT(*) FROM annonces WHERE statut = 'en_attente'"),
    'approuvees'   => compter($pdo, "SELECT COUNT(*) FROM annonces WHERE statut = 'approuvee' AND date_expiration > NOW()"),
    'bientot'      => compter($pdo, "SELECT COUNT(*) FROM annonces WHERE statut = 'approuvee' AND date_expiration > NOW() AND date_expiration <= DATE_ADD(NOW(), INTERVAL 7 DAY)"),
    'utilisateurs' => compter($pdo, "SELECT COUNT(*) FROM utilisateurs"),
    'contacts'     => compter($pdo, "SELECT COUNT(*) FROM contacts WHERE lu = 0"),
];

$en_attente = $pdo->query(
    "SELECT a.id, a.titre, a.prix, a.date_creation, u.nom AS auteur
     FROM annonces a
     JOIN utilisateurs u ON u.id = a.utilisateur_id
     WHERE a.statut = 'en_attente'
     ORDER BY a.date_creation DESC
     LIMIT 5"
)->fetchAll();

$page_title = ta_brut('admin');
require_once __DIR__ . '/../includes/header.php';

$style_carte = 'background:var(--ac-surface); border:1px solid var(--ac-border); border-radius:var(--ac-radius); box-shadow:var(--ac-shadow);';
?>

<main class="container py-5">

    <h1 class="h3 fw-bold mb-4"><?= ta('admin') ?></h1>

    <?= flash_afficher() ?>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-lg">
            <div class="p-3 h-100" style="<?= $style_carte ?>">
                <div class="text-muted-ac small"><?= ta('st_pending') ?></div>
                <div class="fs-2 fw-bold text-warning"><?= (int) $stats['en_attente'] ?></div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg">
            <div class="p-3 h-100" style="<?= $style_carte ?>">
                <div class="text-muted-ac small"><?= ta('st_approved') ?></div>
                <div class="fs-2 fw-bold text-success"><?= (int) $stats['approuvees'] ?></div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg">
            <div class="p-3 h-100" style="<?= $style_carte ?>">
                <div class="text-muted-ac small"><?= ta('st_expiring') ?></div>
                <div class="fs-2 fw-bold"><?= (int) $stats['bientot'] ?></div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg">
            <div class="p-3 h-100" style="<?= $style_carte ?>">
                <div class="text-muted-ac small"><?= ta('st_users') ?></div>
                <div class="fs-2 fw-bold" style="color:var(--ac-blue);"><?= (int) $stats['utilisateurs'] ?></div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg">
            <div class="p-3 h-100" style="<?= $style_carte ?>">
                <div class="text-muted-ac small"><?= ta('st_contacts') ?></div>
                <div class="fs-2 fw-bold"><?= (int) $stats['contacts'] ?></div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mb-4">
        <a class="btn btn-ac-blue" href="<?= BASE_URL ?>admin/annonces.php"><i class="bi bi-card-list me-1"></i><?= ta('nav_ads') ?></a>
        <a class="btn btn-ac-blue" href="<?= BASE_URL ?>admin/utilisateurs.php"><i class="bi bi-people me-1"></i><?= ta('nav_users') ?></a>
        <a class="btn btn-ac-blue" href="<?= BASE_URL ?>admin/categories.php"><i class="bi bi-tags me-1"></i><?= ta('nav_categories') ?></a>
        <a class="btn btn-ac-blue" href="<?= BASE_URL ?>admin/villes.php"><i class="bi bi-geo-alt me-1"></i><?= ta('nav_cities') ?></a>
        <a class="btn btn-ac-blue" href="<?= BASE_URL ?>admin/contacts.php">
            <i class="bi bi-envelope me-1"></i><?= ta('nav_messages') ?>
            <?php if ($stats['contacts'] > 0): ?>
                <span class="badge rounded-pill text-bg-danger ms-1"><?= (int) $stats['contacts'] ?></span>
            <?php endif; ?>
        </a>
    </div>

    <div class="p-4" style="<?= $style_carte ?>">
        <h2 class="h5 fw-bold mb-3"><?= ta('latest_pending') ?></h2>

        <?php if (!$en_attente): ?>
            <p class="text-muted-ac mb-0"><?= ta('no_pending') ?></p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th><?= ta('th_title') ?></th>
                            <th><?= ta('th_author') ?></th>
                            <th><?= ta('th_price') ?></th>
                            <th><?= ta('th_date') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($en_attente as $a): ?>
                            <tr>
                                <td><?= e($a['titre']) ?></td>
                                <td><?= e($a['auteur']) ?></td>
                                <td><?= e(formater_prix($a['prix'], $lang)) ?></td>
                                <td dir="ltr"><?= e(date('d/m/Y H:i', strtotime($a['date_creation']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <a class="btn btn-ac-orange mt-3" href="<?= BASE_URL ?>admin/annonces.php?statut=en_attente"><?= ta('process_ads') ?></a>
        <?php endif; ?>
    </div>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>