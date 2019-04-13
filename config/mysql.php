<?php

$db_config = [
    'username'    => 'root',
    'password'    => '',
    'host'        => 'localhost',
    'database'    => 'world',
    'charset'     => 'utf8',
    'pdo_options' => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]
];

$db_config['DSN'] = 'mysql:host='.$db_config['host'].';dbname='.$db_config['database'].';charset='.$db_config['charset'];

return $db_config;
