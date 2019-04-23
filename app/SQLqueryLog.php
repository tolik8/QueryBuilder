<?php

namespace App;

class SQLqueryLog
{
    public static function save(string $logName, array $data): void
    {
        $filename = ROOT . '/logs/' . $logName . '.log';

        $content = date('Y-m-d') . ' ' . date('H:i:s') . PHP_EOL.PHP_EOL;

        if (isset($_SERVER['REMOTE_ADDR'])) {
            $content .= 'IP: ' . $_SERVER['REMOTE_ADDR'] . PHP_EOL.PHP_EOL;
        }

        foreach ($data as $item) {
            if (!is_array($item)) {
                $content .= $item . PHP_EOL;
            } else {
                foreach ($item as $key => $value) {
                    $content .= ($key .': '. $value) . PHP_EOL;
                }
            }
            $content .= PHP_EOL;
        }

        $content .= '====================================================================' . PHP_EOL;

        $result = @file_put_contents($filename, $content, FILE_APPEND);
        if (!$result) {echo 'Error writing file: ' . $filename;}
    }

}
