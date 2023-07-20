<?php

namespace Offseat\Utils;

class FileSizeFormat {

    /**
     * Display the value as a human readable file size
     */
    public static function bytes2memnicestring(int $value, int $precision = 1): string
    {
        if (!empty($value)) {
            $units = ['B', 'KB', 'MB', 'GB', 'TB'];

            $bytes = max($value, 0);
            $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
            $pow = min($pow, count($units) - 1);
            $bytes /= pow(1024, $pow);

            return round($bytes, $precision) . ' ' . $units[$pow];
        } else {
            return 0;
        }
    }

}
