<?php

namespace Core;

class Render
{
    public static function page($filePath)
    {
        // Get relative path to pages
        $fullPath = dirname(__DIR__) . '/views/pages' . $filePath . '.php';

        // Check if page exists
        if (!is_readable($fullPath)) {
            echo "404";
            exit;
        }

        // Render
        require $fullPath;
        return;
    }

    public static function component($filePath)
    {
        // Get relative path to components
        $fullPath = dirname(__DIR__) . '/views/components' . $filePath . '.php';

        // Check if component exists
        if (!is_readable($fullPath)) {
            echo $fullPath;
            exit;
        }

        // Render
        require $fullPath;
        return;
    }
}
