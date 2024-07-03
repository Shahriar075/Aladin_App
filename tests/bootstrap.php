<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

// Register the error handler
use Symfony\Component\ErrorHandler\ErrorHandler;
ErrorHandler::register(null, false);

// Load Composer's autoloader
require dirname(__DIR__) . '/vendor/autoload.php';

// Load environment variables
if (file_exists(dirname(__DIR__) . '/config/bootstrap.php')) {
    // Include bootstrap file if exists
    require dirname(__DIR__) . '/config/bootstrap.php';
} elseif (class_exists(Dotenv::class) && method_exists(Dotenv::class, 'bootEnv')) {
    // Boot the dotenv file if Dotenv class is available
    (new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');
}
