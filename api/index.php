<?php
// Point d'entrée unique pour Vercel : charge la page demandée.
// Seuls index.php, pages/*.php et admin/*.php sont accessibles.

$racine = dirname(__DIR__);
$chemin = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));

if ($chemin === '/' || $chemin === '') {
    $chemin = '/index.php';
}

if (!preg_match('#^/(index\.php|(pages|admin)/[A-Za-z0-9_-]+\.php)$#', $chemin)) {
    http_response_code(404);
    echo '404 - Page introuvable';
    exit;
}

$fichier = $racine . $chemin;

if (!is_file($fichier)) {
    http_response_code(404);
    echo '404 - Page introuvable';
    exit;
}

$_SERVER['SCRIPT_NAME']     = $chemin;
$_SERVER['PHP_SELF']        = $chemin;
$_SERVER['SCRIPT_FILENAME'] = $fichier;

chdir(dirname($fichier));
require $fichier;