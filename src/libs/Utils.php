<?php
namespace wing\libs;

class Utils {
    /**
     * 或者文件扩展名
     * @param  string $filename
     * @return string
     */
    public static function getFileExtension(string $filename): string
    {
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        return preg_replace('/[^0-9a-z]+/', '', strtolower($ext));
    }

    /**
     *  文件大小格式化
     * @param  int    $bytes
     * @param  int    $precision
     * @return string
     */
    public static function formatFileSize(int $bytes, int $precision = 2): string
    {
        $unit = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        for ($x = 0; $bytes >= 1024 && $x < count($unit); ++$x) {
            $bytes /= 1024;
        }
        return round($bytes, $precision) . '' . $unit[$x];
    }
}