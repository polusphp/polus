<?php

namespace Polus\Controller;

use Aura\Router\Map;
use Polus\App;

interface IController
{
    public static function registerRoutes(Map $map, App $app);
}
