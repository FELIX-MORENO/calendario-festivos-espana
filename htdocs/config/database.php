<?php

/**
 * Configuración de la conexión a la base de datos
 * 
 * Utiliza variables de entorno definidas en .env
 */

declare(strict_types=1);


// Cargar variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Verificar que las variables requeridas existen
$dotenv->required(['DB_HOST', 'DB_NAME', 'DB_USER'])->notEmpty();

return [
    'driver' => 'mysql',
    'host' => $_ENV['DB_HOST'],
    'port' => (int)($_ENV['DB_PORT'] ?? 3306),
    'database' => $_ENV['DB_NAME'],
    'username' => $_ENV['DB_USER'],
    'password' => $_ENV['DB_PASSWORD'] ?? '',
    'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
    ],
    // Configuración de logs
    'log' => [
        'enabled' => ($_ENV['LOG_LEVEL'] ?? 'info') !== 'none',
        'level' => $_ENV['LOG_LEVEL'] ?? 'info', // debug, info, warning, error, none
        'log_dir' => __DIR__ . '/../logs/',
        'prefix' => 'sql_',
        'format' => 'Y-m-d_H',
        'timezone' => 'Europe/Madrid'
    ]
];