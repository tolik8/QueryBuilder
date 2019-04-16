<?php

function vd ()
{
    foreach (func_get_args() as $arg) {
        echo '<pre>';
        /** @noinspection ForgottenDebugOutputInspection */
        var_dump($arg);
        echo '</pre>' . chr(13).chr(10);
    }
}

function dd ()
{
    foreach (func_get_args() as $arg) {vd($arg);} die;
}

function getSQL ($path)
{
    $sql_file = ROOT . '/sql/' . $path;
    if (file_exists($sql_file)) {
        $content = file_get_contents($sql_file);
    } else {
        $content = 'File not found: ' . $sql_file;
    }

    return $content;
}

function filetime ($file)
{
    $filename = ROOT . '/public/' . $file;
    if (file_exists($filename)) {
        return $file . '?tm=' . filemtime($filename);
    }
    return 'File not found ' . $file;
}