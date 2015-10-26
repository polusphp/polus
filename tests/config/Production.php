<?php

namespace Polus\Test\_Config;

use Aura\Di\Config;
use Aura\Di\Container;

class Production extends Config
{
    public function define(Container $di)
    {
        $di->set('debug', function () {
            return true;
        });
    }
}
