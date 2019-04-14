<?php

$root = $_SERVER['DOCUMENT_ROOT'];
if ($root === '') {$root = 'D:/www/qb.loc';}

include $root . '/config/main.php';
include $root . '/app/functions.php';
include $root . '/vendor/autoload.php';

function vd2 ($input)
{
    /** @noinspection ForgottenDebugOutputInspection */
    var_dump($input);
}
