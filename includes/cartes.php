<?php
require_once __DIR__ . '/functions.php';

// Prix lisible : 95 000 DH (ou 95 000 درهم en arabe).
// Le separateur de milliers est une espace insecable (U+00A0) : ainsi,
// en arabe (de droite a gauche), le nombre reste d'un seul bloc et ne s'inverse pas.
function formater_prix($prix, string $lang): string
{
    $nombre = rtrim(rtrim(number_format((float) $prix, 2, ',', "\u{00A0}"), '0'), ',');
    return $nombre . ($lang === 'ar' ? "\u{00A0}درهم" : "\u{00A0}DH");
}

// Carte d'une annonce publique.
// $a doit contenir : id, titre, prix, ville_fr, ville_ar, cat_fr, cat_ar, image
// $favoris : liste des id d'annonces favorites de l'utilisateur connecte
// $retour  : page vers laquelle revenir apres le clic sur le coeur (ex : pages/annonces.php?q=...)
function carte_annonce(array $a, array $favoris, string $retour, string $lang): string
{
    $id      = (int) $a['id'];
    $est_fav = in_array($id, $favoris, true);
    $lien    = BASE_URL . 'pages/details.php?id=' . $id;
    $ville   = $lang === 'ar' ? $a['ville_ar'] : $a['ville_fr'];
    $cat     = $lang === 'ar' ? $a['cat_ar'] : $a['cat_fr'];
    $label   = $lang === 'ar'
        ? ($est_fav ? 'إزالة من المفضلة' : 'إضافة إلى المفضلة')
        : ($est_fav ? 'Retirer des favoris' : 'Ajouter aux favoris');

    ob_start();
    ?>
    <div class="ac-card">
        <div class="ac-card-img">
            <a href="<?= e($lien) ?>">
                <?php if (!empty($a['image'])): ?>
                    <img src="<?= e(BASE_URL . 'uploads/annonces/' . $a['image']) ?>" alt="<?= e($a['titre']) ?>" loading="lazy">
                <?php else: ?>
                    <div class="d-flex align-items-center justify-content-center h-100 text-muted-ac">
                        <i class="bi bi-image fs-1"></i>
                    </div>
                <?php endif; ?>
            </a>

            <form method="post" action="<?= BASE_URL ?>pages/favori.php" class="m-0">
                <?= csrf_field() ?>
                <input type="hidden" name="annonce_id" value="<?= $id ?>">
                <input type="hidden" name="retour" value="<?= e($retour) ?>">
                <button type="submit" class="ac-fav <?= $est_fav ? 'active' : '' ?>" aria-label="<?= e($label) ?>" title="<?= e($label) ?>">
                    <i class="bi <?= $est_fav ? 'bi-heart-fill' : 'bi-heart' ?>"></i>
                </button>
            </form>
        </div>

        <div class="ac-card-body">
            <a href="<?= e($lien) ?>" class="text-decoration-none">
                <h3 class="ac-card-title"><?= e($a['titre']) ?></h3>
            </a>
            <div class="ac-card-meta">
                <i class="bi bi-geo-alt"></i> <?= e($ville) ?> · <?= e($cat) ?>
            </div>
            <div class="ac-price"><?= e(formater_prix($a['prix'], $lang)) ?></div>
        </div>
    </div>
    <?php
    return (string) ob_get_clean();
}

// Liste des id favoris d'un utilisateur
function ids_favoris(PDO $pdo, ?int $utilisateur_id): array
{
    if (!$utilisateur_id) {
        return [];
    }

    $stmt = $pdo->prepare('SELECT annonce_id FROM favoris WHERE utilisateur_id = :u');
    $stmt->execute(['u' => $utilisateur_id]);

    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}