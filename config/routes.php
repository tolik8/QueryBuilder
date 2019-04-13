<?php
/** @noinspection ClassConstantCanBeUsedInspection */

$dispatcher = FastRoute\simpleDispatcher(static function(FastRoute\RouteCollector $r) {
    $r->get('/', ['App\controllers\Home', 'index']);
});
