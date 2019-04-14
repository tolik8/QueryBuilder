<?php

namespace App;

class Log
{
    public static function save (array $debugs, array $data): void
    {
        $log_config = require $_SERVER['DOCUMENT_ROOT'] . '/config/logger.php';
        $directory  = $log_config['Directory'];
        $extension  = $log_config['Extension'];
        $SaveToFile = $log_config['SaveToFile'];

        if (!$SaveToFile) {exit;}

        $content = '';
        $line = [];

        $filename = $directory . '/' . date('Y-m-d') . ' ' . date('His') . '.' . $extension;
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $content .= 'IP: ' . $_SERVER['REMOTE_ADDR'] . CR.CR;
        }

        foreach ($debugs as $key => $item) {
            if (!isset($item['class'])) {continue;}

            if ($item['class'] !== 'DI\Container' && $item['class'] !== 'Invoker\Invoker') {
                if (!isset($item['function'])) {$function_name = '';} else {$function_name = $item['function'];}
                if (!isset($debugs[$key - 1]['line'])) {$line_number = '';} else {$line_number = $debugs[$key - 1]['line'];}
                $line[] = $item['class'] . '->' . $function_name . ' ' . $line_number . CR;
            }
        }
        $line = array_reverse($line);
        foreach ($line as $item) {$content .= $item;}

        $content .= CR;

        foreach ($data as $item) {
            if (!is_array($item)) {
                $content .= $item . CR.CR;
            } else {
                foreach ($item as $key => $value) {
                    $content .= ($key .': '. $value) . CR;
                }
            }
        }

        $content .= '====================================================================' . CR;

        $log_filename = $_SERVER['DOCUMENT_ROOT'] . '/' . $filename;
        $result = @file_put_contents($log_filename, $content, FILE_APPEND);
        if (!$result) {echo 'Error writing file: ' . $log_filename;}
    }
}