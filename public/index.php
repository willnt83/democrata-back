<?php
set_time_limit(60);

if (PHP_SAPI == 'cli-server') {
    // To help the built-in PHP dev server, check if the request was actually for
    // something which should probably be served as a static file
    $url  = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . $url['path'];
    if (is_file($file)) {
        return false;
    }
}

require __DIR__ . '/../vendor/autoload.php';

use Tuupola\Middleware\CorsMiddleware;

session_start();
$config['displayErrorDetails'] = true;
$config['addContentLengthHeader'] = false;

// Produção
$config['db']['host']   = 'br522.hostgator.com.br';
$config['db']['user']   = 'gerado49_democra';
$config['db']['pass']   = 'Pr0DD3m0Cr';
$config['db']['dbname'] = 'gerado49_democrata';

// Homolog
/*
$config['db']['host']   = 'br522.hostgator.com.br';
$config['db']['user']   = 'gerado49_hmdemoc';
$config['db']['pass']   = 'D3m0Cr4t4Pass!@#';
$config['db']['dbname'] = 'gerado49_hmdemocrata';
*/

// Local
/*
$config['db']['host']   = 'localhost';
$config['db']['user']   = 'root';
$config['db']['pass']   = 'asdzxcc11';
$config['db']['dbname'] = 'pcp_prod';
*/


// Instantiate the app
$settings = require __DIR__ . '/../src/settings.php';
$app = new \Slim\App(['settings' => $config]);

// Set up dependencies
require __DIR__ . '/../src/dependencies.php';

// Register middleware
require __DIR__ . '/../src/middleware.php';

// Register routes
require __DIR__ . '/../src/routes.php';

// Run app
$app->run();