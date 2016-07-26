<?php

namespace Polus\Test\Controller;

use Aura\Router\Map;
use Polus\App;
use Polus\Controller\IController;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response\HtmlResponse as Response;

class Test implements IController
{
    public static function registerRoutes(Map $map, App $app)
    {
        $map->attach('world.', '/hello', function ($map) {
            $map->get('world', '/world', [__CLASS__, 'world'])->alias('/yo');
            $map->get('error', '/error', [__CLASS__, 'errorTest']);
            $map->get('name', '/{name}', [__CLASS__, 'name']);
        });
    }

    public function world(ResponseInterface $response)
    {
        $response->getBody()->write("Hello world\n");
        return $response;
    }

    public function errorTest()
    {
        throw new \Exception("Error Processing Request", 1);

        return new Response("Error says hello\n");
    }

    public function name($name)
    {
        return new Response("Hello " . $name . "\n");
    }
}
