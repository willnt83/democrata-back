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

/*
$config['db']['host']   = 'br982.hostgator.com.br';
$config['db']['user']   = 'debora35_pcp';
$config['db']['pass']   = 'PCPSenha123';
$config['db']['dbname'] = 'debora35_pcp';
*/

$config['db']['host']   = 'localhost';
$config['db']['user']   = 'root';
$config['db']['pass']   = 'asdzxcc11';
$config['db']['dbname'] = 'pcp_prod';



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