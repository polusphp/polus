<?php

namespace Polus\Router;

use Aura\Router\Route as BaseRoute;

class Route extends BaseRoute
{
    protected $aliases = [];

    public function alias($path)
    {
        $this->aliases[] = $path;
    }

    public function getAliases()
    {
        return $this->aliases;
    }
}
