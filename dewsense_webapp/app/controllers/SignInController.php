<?php

namespace Controllers;

use Core\Auth;
use Core\Redirect;
use Core\Render;
use Dcblogdev\PdoWrapper\Database;
use PDO;

class SignInController
{
    function signInGET()
    {
        if (Auth::isLoggedIn()) {
            Redirect::to('/dashboard');
        }

        Render::page('/signin');
    }

    function signInPOST()
    {
        if (empty($_POST['username']) || empty($_POST['password'])) {
            Redirect::to('/signin?error=Invalid credentials!');
        }

        $username = $_POST['username'];
        $password = $_POST['password'];

        // Connect to database
        $db = new Database([
            'username' => DB_USERNAME,
            'database' => DB_NAME,
            'password' => DB_PASSWORD,
            'type' => DB_TYPE,
            'charset' => DB_CHARSET,
            'host' => DB_HOST,
            'port' => DB_PORT
        ]);

        // Find the user
        $result = $db->row("SELECT * FROM user WHERE username=?", [$username], PDO::FETCH_ASSOC);

        // If user not found, redirect back to sign in
        if (!$result) {
            Redirect::to('/signin?error=Invalid credentials!');
        }

        if (!password_verify($password, $result['password'])) {
            Redirect::to('/signin?error=Invalid credentials!');
        }

        // Set client session to logged in
        Auth::setLoggedIn(true);
        Redirect::to('/dashboard');
        exit;
    }

    function signOutPOST()
    {
        Auth::setLoggedIn(false);
        Redirect::to('/signin');
        exit;
    }
}
