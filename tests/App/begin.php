<?php

$pattern = '#^.+(?=\\\\tests\\\\App)#';
preg_match($pattern, __DIR__, $matches);
define('ROOT', $matches[0]);

function vd ($input)
{
    /** @noinspection ForgottenDebugOutputInspection */
    var_dump($input);
}
