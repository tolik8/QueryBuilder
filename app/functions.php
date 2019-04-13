<?php

function vd ($input)
{
    echo '<pre>';
    /** @noinspection ForgottenDebugOutputInspection */
    var_dump($input);
    echo '</pre>';
}

function dd ($input)
{
    vd($input); die;
}

function getSQL ($path)
{
    $sql_file = $_SERVER['DOCUMENT_ROOT'] . '/sql/' . $path;
    if (file_exists($sql_file)) {
        $content = file_get_contents($sql_file);
    } else {
        $content = 'File not found: ' . $sql_file;
    }

    return $content;
}

function filetime ($file)
{
    $filename = $_SERVER['DOCUMENT_ROOT'] . '/public/' . $file;
    if (file_exists($filename)) {
        return $file . '?tm=' . filemtime($filename);
    }
    return 'File not found ' . $file;
}