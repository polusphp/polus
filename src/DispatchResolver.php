<?php

namespace Polus;

class DispatchResolver implements DispatchResolverInterface
{
    protected $resolver;

    public function __construct(callable $resolver)
    {
        $this->resolver = $resolver;
    }

    public function resolveController($controllerName)
    {
        $resolver = $this->resolver;
        $controller = $resolver($controllerName);
        return $controller;
    }
}
