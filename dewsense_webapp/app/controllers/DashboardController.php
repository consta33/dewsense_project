<?php

namespace Controllers;

use Core\Auth;
use Core\Redirect;
use Core\Render;

class DashboardController
{
    function index()
    {
        if (!Auth::isLoggedIn()) {
            Redirect::to('/signin');
        }

        Render::page('/dashboard');
    }
}
