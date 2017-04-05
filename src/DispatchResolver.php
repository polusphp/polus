<?php

namespace Polus;

class DispatchResolver implements ResolverInterface
{
    protected $resolver;

    public function __construct(callable $resolver)
    {
        $this->resolver = $resolver;
    }

    public function resolve($cls)
    {
        $resolver = $this->resolver;
        return $resolver($cls);
    }
}
