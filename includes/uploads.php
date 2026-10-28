<?php
// Regles pour les photos des annonces
const UPLOAD_MAX_FILES = 5;
const UPLOAD_MAX_SIZE  = 2 * 1024 * 1024; // 2 Mo par photo
const UPLOAD_TYPES     = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];

// Chemin du dossier uploads/annonces (ou d'un sous-dossier)
function dossier_uploads(string $sous = ''): string
{
    $base = dirname(__DIR__) . '/uploads/annonces';
    return $sous === '' ? $base : $base . '/' . $sous;
}

// Transforme $_FILES['photos'] en liste simple
function normaliser_fichiers(array $f): array
{
    $liste = [];

    if (!isset($f['name']) || !is_array($f['name'])) {
        return $liste;
    }

    foreach ($f['name'] as $i => $nom) {
        if ($f['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $liste[] = [
            'name'     => $nom,
            'tmp_name' => $f['tmp_name'][$i],
            'error'    => $f['error'][$i],
            'size'     => $f['size'][$i],
        ];
    }

    return $liste;
}

/**
 * Verifie les photos envoyees.
 * Retourne [fichiers_valides, codes_erreurs]
 * Codes possibles : too_many, too_big, bad_type, upload
 */
function valider_images(array $files): array
{
    $liste   = normaliser_fichiers($files);
    $valides = [];
    $codes   = [];

    if (count($liste) > UPLOAD_MAX_FILES) {
        return [[], ['too_many']];
    }

    foreach ($liste as $f) {
        if ($f['error'] === UPLOAD_ERR_INI_SIZE
            || $f['error'] === UPLOAD_ERR_FORM_SIZE
            || $f['size'] > UPLOAD_MAX_SIZE) {
            $codes['too_big'] = true;
            continue;
        }

        if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
            $codes['upload'] = true;
            continue;
        }

        // Le type reel est lu dans le contenu du fichier, pas dans son nom
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);

        if (!isset(UPLOAD_TYPES[$mime]) || @getimagesize($f['tmp_name']) === false) {
            $codes['bad_type'] = true;
            continue;
        }

        $valides[] = [
            'tmp_name' => $f['tmp_name'],
            'ext'      => UPLOAD_TYPES[$mime],
        ];
    }

    return [$valides, array_keys($codes)];
}

// Deplace une photo envoyee vers un dossier, avec un nom aleatoire
function enregistrer_image(string $tmp, string $ext, string $dossier): ?string
{
    if (!is_dir($dossier) && !mkdir($dossier, 0755, true)) {
        return null;
    }

    $nom = bin2hex(random_bytes(16)) . '.' . $ext;

    if (!move_uploaded_file($tmp, $dossier . '/' . $nom)) {
        return null;
    }

    return $nom;
}

// Supprime un dossier temporaire et son contenu
function supprimer_dossier(string $dossier): void
{
    if (!is_dir($dossier)) {
        return;
    }

    foreach (glob($dossier . '/*') ?: [] as $fichier) {
        if (is_file($fichier)) {
            unlink($fichier);
        }
    }

    rmdir($dossier);
}