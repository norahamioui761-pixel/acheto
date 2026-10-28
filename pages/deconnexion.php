<?php
require_once __DIR__ . '/../includes/auth.php';

// Deconnexion uniquement par formulaire POST protege par CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verifier()) {
    unset($_SESSION['user_id'], $_SESSION['csrf_token']);
    session_regenerate_id(true);
}

redirect('index.php');