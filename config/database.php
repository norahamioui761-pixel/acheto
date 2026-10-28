<?php
/**
 * Connexion PDO a la base acheto.
 * Utilisation : $pdo = db();
 *
 * - En local (XAMPP) : lit config/env.php.
 * - Sur Vercel : lit les variables d'environnement
 *   DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASSWORD, DB_SSL_CA.
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $ssl_ca = null;

    if (getenv('DB_HOST')) {
        $cfg = [
            'host'     => getenv('DB_HOST'),
            'port'     => getenv('DB_PORT') ?: '3306',
            'dbname'   => getenv('DB_NAME'),
            'user'     => getenv('DB_USER'),
            'password' => getenv('DB_PASSWORD'),
            'charset'  => 'utf8mb4',
        ];

        // Certificat CA (Aiven) fourni dans une variable d'environnement
        $pem = getenv('DB_SSL_CA');
        if ($pem) {
            $ssl_ca = sys_get_temp_dir() . '/acheto-ca.pem';
            if (!is_file($ssl_ca)) {
                file_put_contents($ssl_ca, str_replace('\n', "\n", $pem));
            }
        }
    } else {
        $cfg = require __DIR__ . '/env.php';
    }

    $dsn = 'mysql:host=' . $cfg['host']
         . ';port=' . ($cfg['port'] ?? '3306')
         . ';dbname=' . $cfg['dbname']
         . ';charset=' . $cfg['charset'];

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    if ($ssl_ca !== null) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = $ssl_ca;
    }

    try {
        $pdo = new PDO($dsn, $cfg['user'], $cfg['password'], $options);
    } catch (PDOException $e) {
        error_log('Acheto - erreur PDO : ' . $e->getMessage());
        http_response_code(500);
        exit('Erreur de connexion à la base de données. Vérifiez que MySQL est démarré et que config/env.php est correct.');
    }

    return $pdo;
}