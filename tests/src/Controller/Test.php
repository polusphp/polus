<?php

namespace Polus\Test\Controller;

use Polus\Controller\IController;
use Aura\Router\Map;
use Polus\App;

class Test implements IController
{
    public static function registerRoutes(Map $map, App $app)
    {
        $map->attach('world.', '/hello', function ($map) {
            $map->get('world', '/world', [__CLASS__, 'world'])->alias('/yo');
            $map->get('name', '/{name}', [__CLASS__, 'name']);
        });
    }

    public function world()
    {
        return "Hello world\n";
    }

    public function name($name)
    {
        return "Hello " . $name . "\n";
    }
}
