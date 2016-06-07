<?php

namespace Polus\Controller;

use Aura\Router\Map;
use Polus\App;

interface IController
{
    /**
     * Static method to register controller routes
     *
     * @param  Map    $map Router map
     * @param  App    $app Frontcontroller
     * @return void
     */
    public static function registerRoutes(Map $map, App $app);
}
