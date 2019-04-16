<?php

namespace App;

class SQLqueryLog
{
    public static function save (string $logName, array $data): void
    {
        $filename = ROOT . '/logs/' . $logName . '.log';

        $content = date('Y-m-d') . ' ' . date('H:i:s') . CR.CR;

        if (isset($_SERVER['REMOTE_ADDR'])) {
            $content .= 'IP: ' . $_SERVER['REMOTE_ADDR'] . CR.CR;
        }

        foreach ($data as $item) {
            if (!is_array($item)) {
                $content .= $item . CR.CR;
            } else {
                foreach ($item as $key => $value) {
                    $content .= ($key .': '. $value) . CR;
                }
                $content .= CR;
            }
        }

        $content .= '====================================================================' . CR;

        $result = @file_put_contents($filename, $content, FILE_APPEND);
        if (!$result) {echo 'Error writing file: ' . $filename;}
    }
}