<?php

namespace Controllers;

use Core\Auth;
use Core\Redirect;

class MainController
{
    function router()
    {
        if (!Auth::isLoggedIn()) {
            Redirect::to('/signin');
        }

        Redirect::to('/dashboard');
    }
}
