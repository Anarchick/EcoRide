<?php

/**
 * Export MongoDB schema (indexes) to JavaScript format
 * 
 * This script connects to MongoDB and exports all indexes
 * in a format that can be executed in MongoDB shell
 */

require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/.env');

try {
    $client = new MongoDB\Client($_ENV['MONGODB_URI']);
    $database = $client->selectDatabase($_ENV['MONGODB_DB']);
    
    echo "// MongoDB Schema Export - " . date('Y-m-d H:i:s') . PHP_EOL;
    echo "// Database: " . $_ENV['MONGODB_DB'] . PHP_EOL;
    echo "// Generated from Doctrine ODM Documents" . PHP_EOL;
    echo PHP_EOL;
    echo "use " . $_ENV['MONGODB_DB'] . ";" . PHP_EOL;
    echo PHP_EOL;
    
    $collections = $database->listCollections();
    
    foreach ($collections as $collectionInfo) {
        $collectionName = $collectionInfo->getName();
        
        echo "// Collection: {$collectionName}" . PHP_EOL;
        echo "db.createCollection('{$collectionName}');" . PHP_EOL;
        echo PHP_EOL;
        
        $collection = $database->selectCollection($collectionName);
        $indexes = $collection->listIndexes();
        
        foreach ($indexes as $index) {
            $indexName = $index['name'];
            
            // Skip default _id index
            if ($indexName === '_id_') {
                continue;
            }
            
            $keys = json_encode($index['key'], JSON_UNESCAPED_SLASHES);
            $options = [];
            
            if (isset($index['unique']) && $index['unique']) {
                $options['unique'] = true;
            }
            
            if (isset($index['expireAfterSeconds'])) {
                $options['expireAfterSeconds'] = $index['expireAfterSeconds'];
            }
            
            $optionsJson = !empty($options) 
                ? ', ' . json_encode($options, JSON_UNESCAPED_SLASHES) 
                : '';
            
            echo "db.{$collectionName}.createIndex({$keys}{$optionsJson});" . PHP_EOL;
        }
        
        echo PHP_EOL;
    }
    
    echo "// End of schema export" . PHP_EOL;
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
