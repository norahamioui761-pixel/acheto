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

// Cloudinary est utilise si les 3 variables d'environnement existent (Vercel).
// Sinon (XAMPP), les photos restent dans uploads/annonces/.
function cloudinary_actif(): bool
{
    return getenv('CLOUDINARY_CLOUD_NAME')
        && getenv('CLOUDINARY_API_KEY')
        && getenv('CLOUDINARY_API_SECRET');
}

// Envoie un fichier vers Cloudinary (envoi signe). Retourne true si OK.
function cloudinary_envoyer(string $tmp, string $public_id): bool
{
    if (!function_exists('curl_init')) {
        error_log('Acheto - cURL indisponible');
        return false;
    }

    $cloud     = getenv('CLOUDINARY_CLOUD_NAME');
    $cle       = getenv('CLOUDINARY_API_KEY');
    $secret    = getenv('CLOUDINARY_API_SECRET');
    $timestamp = time();
    $signature = sha1('public_id=' . $public_id . '&timestamp=' . $timestamp . $secret);

    $ch = curl_init('https://api.cloudinary.com/v1_1/' . $cloud . '/image/upload');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => [
            'file'      => new CURLFile($tmp),
            'api_key'   => $cle,
            'timestamp' => $timestamp,
            'public_id' => $public_id,
            'signature' => $signature,
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
    ]);

    $reponse = curl_exec($ch);
    $code    = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($reponse === false || $code !== 200) {
        error_log('Acheto - Cloudinary HTTP ' . $code . ' : ' . (string) $reponse);
        return false;
    }

    return true;
}

// Adresse (URL) d'une photo du brouillon
function url_image_temporaire(string $dossier, string $nom): string
{
    if (cloudinary_actif()) {
        return 'https://res.cloudinary.com/' . getenv('CLOUDINARY_CLOUD_NAME')
             . '/image/upload/acheto/' . $dossier . '/' . $nom;
    }

    return BASE_URL . 'uploads/annonces/' . $dossier . '/' . $nom;
}

// Verifie qu'une photo du brouillon existe
function image_temporaire_existe(string $dossier, string $nom): bool
{
    return cloudinary_actif() || is_file(dossier_uploads($dossier) . '/' . $nom);
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

// Enregistre une photo envoyee, avec un nom aleatoire.
// Cloudinary si disponible, sinon deplacement dans le dossier local.
function enregistrer_image(string $tmp, string $ext, string $dossier): ?string
{
    $nom = bin2hex(random_bytes(16)) . '.' . $ext;

    if (cloudinary_actif()) {
        $public_id = 'acheto/' . basename($dossier) . '/' . pathinfo($nom, PATHINFO_FILENAME);
        return cloudinary_envoyer($tmp, $public_id) ? $nom : null;
    }

    if (!is_dir($dossier) && !mkdir($dossier, 0755, true)) {
        return null;
    }

    if (!move_uploaded_file($tmp, $dossier . '/' . $nom)) {
        return null;
    }

    return $nom;
}

// Rattache une photo du brouillon a l'annonce et retourne la valeur a
// enregistrer dans images_annonces.chemin
// (URL complete avec Cloudinary, "id/nom.jpg" en local).
function finaliser_image(string $dossier_tmp, string $nom, int $annonce_id): string
{
    if (cloudinary_actif()) {
        return url_image_temporaire($dossier_tmp, $nom);
    }

    $dossier_final = dossier_uploads((string) $annonce_id);

    if (!is_dir($dossier_final) && !mkdir($dossier_final, 0755, true)) {
        throw new RuntimeException('Dossier final impossible a creer');
    }

    if (!rename(dossier_uploads($dossier_tmp) . '/' . $nom, $dossier_final . '/' . $nom)) {
        throw new RuntimeException('Deplacement de la photo impossible');
    }

    return $annonce_id . '/' . $nom;
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