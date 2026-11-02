<?php
// Session securisee
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

// En local (XAMPP) le site est dans /ACHETO.PROJET/ ; sur Vercel il est à la racine.
if (!defined('BASE_URL')) {
    define(
        'BASE_URL',
        (strpos($_SERVER['REQUEST_URI'] ?? '', '/ACHETO.PROJET/') === 0) ? '/ACHETO.PROJET/' : '/'
    );
}

// Echappement des donnees affichees (protection XSS)
function e($valeur): string
{
    return htmlspecialchars((string) $valeur, ENT_QUOTES, 'UTF-8');
}

// Redirection vers une page du site
function redirect(string $chemin)
{
    header('Location: ' . BASE_URL . ltrim($chemin, '/'));
    exit;
}

// Adresse d'une photo d'annonce a partir de la valeur de images_annonces.chemin :
// URL complete (Cloudinary) ou "id/nom.jpg" (fichier local dans uploads/annonces/).
function url_image(string $chemin): string
{
    if (preg_match('#^https?://#i', $chemin)) {
        return $chemin;
    }

    return BASE_URL . 'uploads/annonces/' . $chemin;
}

// Protection CSRF
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_verifier(): bool
{
    $envoye = $_POST['csrf_token'] ?? '';
    return is_string($envoye)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $envoye);
}

// Messages temporaires (succes / erreur)
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flash_afficher(): string
{
    if (empty($_SESSION['flash'])) {
        return '';
    }

    $html = '';
    foreach ($_SESSION['flash'] as $f) {
        $type = in_array($f['type'], ['success', 'danger', 'warning', 'info'], true) ? $f['type'] : 'info';
        $html .= '<div class="alert alert-' . $type . '" role="alert">' . e($f['message']) . '</div>';
    }

    unset($_SESSION['flash']);
    return $html;
}