<?php
require_once __DIR__ . '/../includes/auth.php';

// Ajoute ou retire une annonce des favoris (POST uniquement, protege par CSRF)
$user = exiger_connexion();
$pdo  = db();

$retour = 'pages/favoris.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Redirection autorisee uniquement vers index.php ou une page du dossier pages/
    $demande = $_POST['retour'] ?? '';
    if (is_string($demande) && preg_match('/^(?:pages\/[a-z\-]+|index)\.php(\?[A-Za-z0-9=&_%.\-]*)?$/', $demande)) {
        $retour = $demande;
    }

    if (csrf_verifier()) {
        $annonce_id = (int) ($_POST['annonce_id'] ?? 0);

        // Deja en favori ? alors on le retire
        $stmt = $pdo->prepare('SELECT 1 FROM favoris WHERE utilisateur_id = :u AND annonce_id = :a');
        $stmt->execute(['u' => $user['id'], 'a' => $annonce_id]);

        if ($stmt->fetch()) {
            $del = $pdo->prepare('DELETE FROM favoris WHERE utilisateur_id = :u AND annonce_id = :a');
            $del->execute(['u' => $user['id'], 'a' => $annonce_id]);
        } else {
            // Ajout possible uniquement pour une annonce publique (approuvee et non expiree)
            $stmt = $pdo->prepare(
                "SELECT id FROM annonces
                 WHERE id = :a AND statut = 'approuvee' AND date_expiration > NOW()"
            );
            $stmt->execute(['a' => $annonce_id]);

            if ($stmt->fetch()) {
                $ins = $pdo->prepare('INSERT IGNORE INTO favoris (utilisateur_id, annonce_id) VALUES (:u, :a)');
                $ins->execute(['u' => $user['id'], 'a' => $annonce_id]);
            }
        }
    }
}

redirect($retour);