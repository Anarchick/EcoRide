<?php

require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/.env');

echo "MONGODB_URI: " . $_ENV['MONGODB_URI'] . PHP_EOL;
echo "MONGODB_DB: " . $_ENV['MONGODB_DB'] . PHP_EOL;
echo PHP_EOL;

// Test de connexion
try {
    $client = new MongoDB\Client($_ENV['MONGODB_URI']);
    $database = $client->selectDatabase($_ENV['MONGODB_DB']);
    
    echo "✅ Connexion réussie à MongoDB" . PHP_EOL;
    echo "📂 Base de données: " . $database->getDatabaseName() . PHP_EOL;
    echo PHP_EOL;
    
    echo "📋 Collections disponibles:" . PHP_EOL;
    foreach ($database->listCollections() as $collection) {
        echo "  - " . $collection->getName() . PHP_EOL;
    }
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . PHP_EOL;
}
