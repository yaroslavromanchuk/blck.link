<?php

namespace common\components;

use Yii;

class ReportFilePath
{
    public static function webRoot(): string
    {
        return rtrim(Yii::getAlias('@backend/web'), '/\\') . DIRECTORY_SEPARATOR;
    }

    public static function directory(string $type): string
    {
        $type = in_array($type, ['xls', 'pdf'], true) ? $type : 'xls';
        $directory = self::webRoot() . $type . DIRECTORY_SEPARATOR;

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException("Не вдалося створити каталог звітів: {$directory}");
        }

        return $directory;
    }

    public static function xls(string $filename): string
    {
        return self::directory('xls') . $filename;
    }

    public static function pdf(string $filename): string
    {
        return self::directory('pdf') . $filename;
    }

    public static function webUrl(string $type, string $filename): string
    {
        return '/' . trim($type, '/') . '/' . ltrim($filename, '/');
    }
}

