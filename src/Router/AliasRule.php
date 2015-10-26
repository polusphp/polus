<?php

namespace Polus\Router;

use Aura\Router\Rule\Path;
use Aura\Router\Route as AuraRoute;
use Polus\Router\Route as PolusRoute;
use Psr\Http\Message\ServerRequestInterface;

class AliasRule extends Path
{
    public function __invoke(ServerRequestInterface $request, AuraRoute $route)
    {
        $match = preg_match(
            $this->buildRegex($route),
            $request->getUri()->getPath(),
            $matches
        );
        if (! $match) {
            if ($route instanceof PolusRoute) {
                $aliases = $route->getAliases();
                if ($aliases) {
                    $this->route = $route;
                    foreach ($aliases as $path) {
                        $match = preg_match(
                            $this->buildAliasRegex($path),
                            $request->getUri()->getPath(),
                            $matches
                        );
                    }
                }

            }
            if (!$match) {
                return false;
            }
        }
        $route->attributes($this->getAttributes($matches, $route->wildcard));
        return true;
    }

    protected function buildAliasRegex($path)
    {
        $this->regex = $this->basepath . $path;
        $this->setRegexOptionalAttributes();
        $this->setRegexAttributes();
        $this->setRegexWildcard();
        $this->regex = '#^' . $this->regex . '$#';
        return $this->regex;
    }
}
