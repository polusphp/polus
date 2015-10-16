<?php
date_default_timezone_set('UTC');
define('__ROOT__', dirname(__DIR__));
require __ROOT__.'/vendor/autoload.php';

use Polus\App;
use Aura\Router\Map;

class TestController implements Polus\Controller\IController
{
    public static function registerRoutes(Map $map, App $app)
    {
        $map->attach('world.', '/hello', function ($map) {
            $map->get('world', '/world', ['TestController', 'world']);
            $map->get('name', '/{name}', ['TestController', 'name']);
        });
    }

    public function world()
    {
        return "Hello world";
    }

    public function name($name)
    {
        return "Hello" . $name;
    }
}

$appNs = 'Vendor';

$app = new App($appNs);

$app->registerController('TestController');
$app->run();
