<?php
// Set timezone for Cyprus
date_default_timezone_set('Asia/Nicosia');
session_start();

use Controllers\APIController;
use Controllers\DashboardController;
use Controllers\MainController;
use Controllers\SignInController;
use Core\Redirect;

require dirname(__DIR__) . '/vendor/autoload.php';

// Requires the config.php to use defined constants
require dirname(__DIR__) . '/app/config/config.php';

$dispatcher = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r) {

    $r->get('/', MainController::class . '/router');
    $r->get('/signin', SignInController::class . '/signInGET');
    $r->post('/signin', SignInController::class . '/signInPOST');
    $r->post('/signout', SignInController::class . '/signOutPOST');
    $r->get('/dashboard', DashboardController::class . '/index');

    $r->addGroup('/api', function (FastRoute\RouteCollector $r) {
        $r->get('/get-humidity', APIController::class . '/getHumidityJSON');
    });
});

// Fetch method and URI from somewhere
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Strip query string (?foo=bar) and decode URI
$pos = strpos($uri, '?');

if ($pos !== false) {
    $uri = substr($uri, 0, $pos);
}

$uri = rawurldecode($uri);
$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        // 404 Not Found
        Redirect::to('/');
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        // 405 Method Not Allowed
        break;
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];
        // Call $handler with $vars
        list($class, $method) = explode('/', $handler, 2);
        if (class_exists($class) && method_exists($class, $method)) {
            call_user_func(array(new $class, $method), $vars);
        }
        break;
}
