<?php

namespace Polus\_Config;

use Aura\Di\Config;
use Aura\Di\Container;
use Zend\Diactoros\ServerRequestFactory as RequestFactory;

class Common extends Config
{
    const VERSION = '0.0.0';
    public function define(Container $di)
    {
        if (!$di->has('response')) {
            $di->set('response', $di->lazyNew('Zend\Diactoros\Response'));
        }
        if (!$di->has('request')) {
            $di->set('request', RequestFactory::fromGlobals());
        }
        $di->setter['Polus\Traits\ResponseTrait']['setResponse'] = $di->lazyGet('response');
    }
}
