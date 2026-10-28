<?php
/**
 * Connexion PDO a la base acheto.
 * Utilisation : $pdo = db();
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $cfg = require __DIR__ . '/env.php';

    $dsn = 'mysql:host=' . $cfg['host']
         . ';dbname=' . $cfg['dbname']
         . ';charset=' . $cfg['charset'];

    try {
        $pdo = new PDO($dsn, $cfg['user'], $cfg['password'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        error_log('Acheto - erreur PDO : ' . $e->getMessage());
        http_response_code(500);
        exit('Erreur de connexion à la base de données. Vérifiez que MySQL est démarré et que config/env.php est correct.');
    }

    return $pdo;
}