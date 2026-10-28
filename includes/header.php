<?php
require_once __DIR__ . '/lang.php';
require_once __DIR__ . '/auth.php';

$base = '/ACHETO.PROJET/';
$page_title = $page_title ?? 'Acheto';

$user = utilisateur_courant();

// Nombre de messages non lus (annonces de l'utilisateur)
$nb_non_lus = 0;
if ($user) {
    $stmt = db()->prepare(
        'SELECT COUNT(*)
         FROM messages m
         JOIN annonces a ON a.id = m.annonce_id
         WHERE a.utilisateur_id = :u AND m.lu = 0'
    );
    $stmt->execute(['u' => $user['id']]);
    $nb_non_lus = (int) $stmt->fetchColumn();
}

$hx = [
    'fr' => [
        'my_ads'        => 'Mes annonces',
        'messages'      => 'Messages',
        'profile'       => 'Mon profil',
        'favs'          => 'Favoris',
        'admin'         => 'Administration',
        'logout'        => 'Déconnexion',
        'publish_short' => 'Publier',
    ],
    'ar' => [
        'my_ads'        => 'إعلاناتي',
        'messages'      => 'الرسائل',
        'profile'       => 'ملفي الشخصي',
        'favs'          => 'المفضلة',
        'admin'         => 'الإدارة',
        'logout'        => 'تسجيل الخروج',
        'publish_short' => 'انشر',
    ],
][$lang];

$params_lang = $_GET;
$params_lang['lang'] = ($lang === 'ar') ? 'fr' : 'ar';
$lien_lang = '?' . http_build_query($params_lang);
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $dir ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') ?> | Acheto</title>

    <link rel="icon" href="<?= $base ?>assets/logo.jpeg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">

    <?php if ($dir === 'rtl'): ?>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <?php else: ?>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php endif; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= $base ?>css/style.css" rel="stylesheet">

    <style>
        .ac-header-inner { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; padding-block: .5rem; }
        .ac-header .ac-logo img { max-height: 42px; width: auto; }
        .ac-nav a { white-space: nowrap; }
        .ac-search-form { flex: 1 1 100%; margin: 0; }
        .ac-actions { flex: 1 1 100%; display: flex; align-items: center; gap: .5rem; }
        .ac-actions .btn-ac-orange { white-space: nowrap; margin-inline-start: auto; }
        @media (min-width: 1200px) {
            .ac-search-form { flex: 1 1 auto; }
            .ac-actions { flex: 0 0 auto; }
            .ac-actions .btn-ac-orange { margin-inline-start: 0; }
        }
    </style>

    <script>
        (function () {
            var theme = 'light';
            try { theme = localStorage.getItem('acheto-theme') || 'light'; } catch (e) {}
            document.documentElement.setAttribute('data-theme', theme);
            document.documentElement.setAttribute('data-bs-theme', theme);
        })();
    </script>
</head>
<body>

<header class="ac-header">
    <div class="container ac-header-inner">

        <a class="ac-logo order-1" href="<?= $base ?>index.php">
            <img src="<?= $base ?>assets/logo.jpeg" alt="Acheto">
        </a>

        <!-- Liens de navigation : ordinateur -->
        <div class="ac-nav d-none d-xl-flex gap-1 order-xl-2">
            <a href="<?= $base ?>index.php"><?= t('nav_home') ?></a>
            <a href="<?= $base ?>pages/annonces.php"><?= t('nav_ads') ?></a>
            <a href="<?= $base ?>pages/contact.php"><?= t('nav_contact') ?></a>
            <a href="<?= $base ?>pages/a-propos.php"><?= t('nav_about') ?></a>
        </div>

        <!-- Menu hamburger : telephone et tablette -->
        <div class="dropdown d-xl-none order-2 ms-auto">
            <button class="ac-icon-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Menu">
                <i class="bi bi-list fs-4"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="<?= $base ?>index.php"><i class="bi bi-house me-2"></i><?= t('nav_home') ?></a></li>
                <li><a class="dropdown-item" href="<?= $base ?>pages/annonces.php"><i class="bi bi-grid me-2"></i><?= t('nav_ads') ?></a></li>
                <li><a class="dropdown-item" href="<?= $base ?>pages/contact.php"><i class="bi bi-chat-dots me-2"></i><?= t('nav_contact') ?></a></li>
                <li><a class="dropdown-item" href="<?= $base ?>pages/a-propos.php"><i class="bi bi-info-circle me-2"></i><?= t('nav_about') ?></a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="<?= $base ?>pages/favoris.php"><i class="bi bi-heart me-2"></i><?= e($hx['favs']) ?></a></li>
            </ul>
        </div>

        <!-- Recherche : toujours visible -->
        <form class="ac-search-form order-3 order-xl-3" action="<?= $base ?>pages/annonces.php" method="get">
            <input class="form-control" type="search" name="q" placeholder="<?= t('search_ph') ?>"
                   aria-label="<?= t('search_btn') ?>">
        </form>

        <!-- Actions : toujours visibles -->
        <div class="ac-actions order-4 order-xl-4">

            <a class="ac-icon-btn d-none d-xl-inline-flex" href="<?= $base ?>pages/favoris.php" aria-label="<?= e($hx['favs']) ?>">
                <i class="bi bi-heart"></i>
            </a>

            <button class="ac-icon-btn" type="button" id="themeToggle" aria-label="Mode clair / sombre">
                <i class="bi bi-moon-stars" id="themeIcon"></i>
            </button>

            <a class="ac-icon-btn" href="<?= e($lien_lang) ?>">
                <?= $lang === 'ar' ? 'Français' : 'عربية' ?>
            </a>

            <?php if ($user): ?>
                <div class="dropdown">
                    <button class="ac-icon-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"
                            aria-label="<?= e($user['nom']) ?>">
                        <i class="bi bi-person-circle"></i>
                        <span class="d-none d-sm-inline ms-1"><?= e(mb_strimwidth($user['nom'], 0, 18, '…', 'UTF-8')) ?></span>
                        <?php if ($nb_non_lus > 0): ?>
                            <span class="badge rounded-pill text-bg-danger ms-1"><?= (int) $nb_non_lus ?></span>
                        <?php endif; ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?= $base ?>pages/mes-annonces.php"><i class="bi bi-card-list me-2"></i><?= e($hx['my_ads']) ?></a></li>
                        <li>
                            <a class="dropdown-item" href="<?= $base ?>pages/messages.php">
                                <i class="bi bi-envelope me-2"></i><?= e($hx['messages']) ?>
                                <?php if ($nb_non_lus > 0): ?>
                                    <span class="badge rounded-pill text-bg-danger ms-1"><?= (int) $nb_non_lus ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li><a class="dropdown-item" href="<?= $base ?>pages/favoris.php"><i class="bi bi-heart me-2"></i><?= e($hx['favs']) ?></a></li>
                        <li><a class="dropdown-item" href="<?= $base ?>pages/profil.php"><i class="bi bi-person me-2"></i><?= e($hx['profile']) ?></a></li>
                        <?php if ($user['role'] === 'admin'): ?>
                            <li><a class="dropdown-item" href="<?= $base ?>admin/index.php"><i class="bi bi-shield-lock me-2"></i><?= e($hx['admin']) ?></a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="post" action="<?= $base ?>pages/deconnexion.php" class="m-0">
                                <?= csrf_field() ?>
                                <button type="submit" class="dropdown-item"><i class="bi bi-box-arrow-right me-2"></i><?= e($hx['logout']) ?></button>
                            </form>
                        </li>
                    </ul>
                </div>
            <?php else: ?>
                <a class="ac-icon-btn" href="<?= $base ?>pages/connexion.php" aria-label="<?= t('login') ?>">
                    <i class="bi bi-person"></i>
                    <span class="d-none d-sm-inline ms-1"><?= t('login') ?></span>
                </a>
            <?php endif; ?>

            <a class="btn btn-ac-orange" href="<?= $base ?>pages/publier.php">
                <i class="bi bi-plus-circle me-1"></i>
                <span class="d-sm-none"><?= e($hx['publish_short']) ?></span>
                <span class="d-none d-sm-inline"><?= t('publish') ?></span>
            </a>

        </div>

    </div>
</header>