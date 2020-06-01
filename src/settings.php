<?php
return [
    'settings' => [
        'appType' => isset($_ENV['APP_TYPE']) ? isset($_ENV['APP_TYPE']) : 'development',
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header

        // Renderer settings
        'renderer' => [
            'template_path' => __DIR__ . '/../templates/',
        ],        

        // Monolog settings
        'logger' => [
            'name' => 'slim-app',
            'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
            'level' => \Monolog\Logger::DEBUG,
        ],

        // Database settings
        'db' => [
            'host'      => isset($_ENV['DB_HOST']) ? $_ENV['DB_HOST'] : '',
            'user'      => isset($_ENV['DB_USER']) ? $_ENV['DB_USER'] : '',
            'pass'      => isset($_ENV['DB_PASS']) ? $_ENV['DB_PASS'] : '',
            'dbname'    => isset($_ENV['DB_NAME']) ? $_ENV['DB_NAME'] : ''
        ]
    ],
];
