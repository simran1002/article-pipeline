<?php

use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Illuminate\Routing\Router;
use Illuminate\Events\Dispatcher;

$app = new Container();
$app->instance('path', $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__));
$app->instance('path.base', $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__));

// Set facade root for facades to work
Facade::setFacadeApplication($app);

// Register router
$router = new Router(new Dispatcher($app), $app);
$app->instance('router', $router);
$app->alias('router', Router::class);
$app->instance(Router::class, $router);

// Bind Route facade to router
Illuminate\Support\Facades\Route::setFacadeApplication($app);

// Register singletons
$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

// Load routes directly
$basePath = dirname(__DIR__);
if (file_exists($basePath . '/routes/api.php')) {
    require $basePath . '/routes/api.php';
}
if (file_exists($basePath . '/routes/web.php')) {
    require $basePath . '/routes/web.php';
}

return $app;
