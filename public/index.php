<?php
/** @noinspection ClassConstantCanBeUsedInspection */

if (!session_id()) {@session_start();}
header('Content-Type: text/html; charset=utf-8');
date_default_timezone_set('Europe/Kiev');

require_once '../config/main.php';
require_once '../app/functions.php';
require_once '../vendor/autoload.php';

use DI\ContainerBuilder;
$containerBuilder = new ContainerBuilder;

$containerBuilder->addDefinitions([

    PDO::class => static function() {
        $db_config = require '../config/mysql.php';
        try {
            return new \PDO($db_config['DSN'], $db_config['username'], $db_config['password'], $db_config['pdo_options']);
        } catch (\PDOException $e) {
            echo $e->getMessage();
        }
        return null;
    }
]);

try {
    $container = $containerBuilder->build();
} catch (\Exception $e) {
    echo $e->getMessage();
}

require '../config/routes.php';

$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

if (false !== $pos = strpos($uri, '?')) {$uri = substr($uri, 0, $pos);}
$uri = rawurldecode($uri);
$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        $container->call(['App\controllers\Error', 'e404']); break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        $container->call(['App\controllers\Error', 'e405']); break;
    case FastRoute\Dispatcher::FOUND:
        $container->call($routeInfo[1], $routeInfo[2]); break;
}