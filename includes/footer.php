<?php
require_once __DIR__ . '/lang.php';
$base = '/ACHETO.PROJET/';
?>
<footer class="ac-footer">
    <div class="container">
        <div class="row g-4">

            <div class="col-12 col-md-5">
                <p class="fs-4 fw-bold mb-1">Ache<span style="color:#FF8B35;">to</span></p>
                <p class="mb-1"><?= t('tagline') ?></p>
                <p class="small mb-0"><?= t('footer_about') ?></p>
            </div>

            <div class="col-6 col-md-3">
                <h6><?= t('footer_links') ?></h6>
                <ul class="list-unstyled small mb-0">
                    <li class="mb-1"><a href="<?= $base ?>index.php"><?= t('nav_home') ?></a></li>
                    <li class="mb-1"><a href="<?= $base ?>pages/annonces.php"><?= t('nav_ads') ?></a></li>
                    <li class="mb-1"><a href="<?= $base ?>pages/contact.php"><?= t('nav_contact') ?></a></li>
                    <li class="mb-1"><a href="<?= $base ?>pages/a-propos.php"><?= t('nav_about') ?></a></li>
                </ul>
            </div>

            <div class="col-6 col-md-4">
                <h6>Acheto</h6>
                <ul class="list-unstyled small mb-0">
                    <li class="mb-1"><a href="<?= $base ?>pages/conditions.php"><?= t('terms') ?></a></li>
                    <li class="mb-1"><a href="<?= $base ?>pages/confidentialite.php"><?= t('privacy') ?></a></li>
                </ul>
            </div>

        </div>

        <hr style="border-color: rgba(255,255,255,.2);">
        <p class="text-center small mb-0">&copy; <?= date('Y') ?> Acheto. <?= t('rights') ?></p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $base ?>js/script.js"></script>
</body>
</html>