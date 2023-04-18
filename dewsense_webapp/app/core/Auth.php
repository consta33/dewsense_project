<?php

namespace Core;

class Auth
{
    public static function setLoggedIn($loggedInValue)
    {
        $_SESSION['is_logged_in'] = $loggedInValue;
    }

    public static function isLoggedIn()
    {
        if (empty($_SESSION['is_logged_in'])) {
            return false;
        }

        return $_SESSION['is_logged_in'];
    }
}
