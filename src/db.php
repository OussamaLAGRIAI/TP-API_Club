<?php
// db.php — connexion PDO PostgreSQL. TP 6 : plus aucune valeur par défaut, une variable manquante arrête tout.
// Le service api joint le service db par son nom sur le réseau de la pile Compose.
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        foreach (['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASSWORD'] as $nom) {
            if (getenv($nom) === false || getenv($nom) === '') {
                throw new RuntimeException("Variable d'environnement manquante : $nom");
            }
        }
        $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s',
                       getenv('DB_HOST'), getenv('DB_PORT') ?: '5432', getenv('DB_NAME'));
        $pdo = new PDO($dsn, getenv('DB_USER'), getenv('DB_PASSWORD'), [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // toute erreur SQL lève une exception
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // tableaux associatifs
            PDO::ATTR_EMULATE_PREPARES   => false,                   // vraies requêtes préparées
        ]);
    }
    return $pdo;
}
