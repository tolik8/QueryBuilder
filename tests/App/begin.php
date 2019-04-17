<?php

$pattern = '#^.+(?=\\\\tests\\\\App)#';
preg_match($pattern, __DIR__, $matches);
define('CR', chr(13).chr(10));
define('ROOT', $matches[0]);

function vd ($input)
{
    /** @noinspection ForgottenDebugOutputInspection */
    var_dump($input);
}
