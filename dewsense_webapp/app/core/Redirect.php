<?php

namespace Core;

class Redirect
{
    public static function to($path)
    {
        $host = $_SERVER['HTTP_HOST'];
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
        $url = $protocol . '://' . $host . $path;
        header("Location: " . $url, true, 302);
        exit;
    }
}
