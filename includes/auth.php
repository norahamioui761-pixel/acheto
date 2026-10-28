<?php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../config/database.php';

// Ouvre la session de l'utilisateur apres une connexion reussie
function connecter_utilisateur(array $utilisateur): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $utilisateur['id'];
}

// Ferme completement la session
function deconnecter_utilisateur(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }

    session_destroy();
}

// Retourne l'utilisateur connecte (ou null)
function utilisateur_courant(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    $stmt = db()->prepare(
        'SELECT id, nom, email, telephone, ville_id, role
         FROM utilisateurs
         WHERE id = :id AND actif = 1'
    );
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $utilisateur = $stmt->fetch();

    if (!$utilisateur) {
        unset($_SESSION['user_id']);
        return null;
    }

    return $utilisateur;
}

function est_connecte(): bool
{
    return utilisateur_courant() !== null;
}

// A mettre en haut des pages reservees aux membres
function exiger_connexion(): array
{
    $utilisateur = utilisateur_courant();

    if ($utilisateur === null) {
        flash('warning', 'Veuillez vous connecter pour continuer.');
        redirect('pages/connexion.php');
    }

    return $utilisateur;
}

// A mettre en haut des pages reservees a l'administrateur
function exiger_admin(): array
{
    $utilisateur = exiger_connexion();

    if ($utilisateur['role'] !== 'admin') {
        http_response_code(403);
        exit('Accès refusé.');
    }

    return $utilisateur;
}