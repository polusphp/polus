<?php

namespace Polus\_Config;

use Aura\Di\Config;
use Aura\Di\Container;
use Aura\Router\Rule;
use Polus\Router\AliasRule;
use Polus\Router\Route;
use Zend\Diactoros\ServerRequestFactory as RequestFactory;

class Common extends Config
{
    public function define(Container $di)
    {
        if (!isset($di->params['Polus\Middleware\Router']['router'])) {
            $di->params['Polus\Middleware\Router']['router'] = $di->lazyGet('polus:router_container');
        }
        if (!$di->has('polus:middlewares')) {
            $di->set('polus:middlewares', function () use ($di) {
                $queue = [];
                if ($di->has('mode:middlewares')) {
                    $queue = $di->get('mode:middlewares');
                }
                $queue = [];
                if (php_sapi_name() !== 'cli') {
                    $queue[] = $di->newInstance('Relay\Middleware\ResponseSender');
                } else {
                    $queue[] = $di->newInstance('Polus\Middleware\CliResponseSender');
                }
                $queue[] = $di->newInstance('Franzl\Middleware\Whoops\Middleware');
                if ($di->has('mode:middlewares:preRouter')) {
                    $queue = array_merge($queue, $di->get('mode:middlewares:preRouter'));
                }
                $queue[] = $di->newInstance('Polus\Middleware\Router');
                if ($di->has('mode:middlewares:postRouter')) {
                    $queue = array_merge($queue, $di->get('mode:middlewares:postRouter'));
                }
                $queue[] = $di->newInstance('Polus\Middleware\Status404');
                $queue[] = $di->newInstance('Relay\Middleware\FormContentHandler');
                $queue[] = $di->newInstance('Relay\Middleware\JsonContentHandler', [
                    'assoc' => true,
                ]);
                if ($di->has('mode:middlewares:preDispatcher')) {
                    $queue = array_merge($queue, $di->get('mode:middlewares:preDispatcher'));
                }
                return $queue;
            });
        }
        if (!$di->has('relay')) {
            $di->set('relay', $di->lazyNew('Relay\RelayBuilder'));
        }
        if (!$di->has('polus:response')) {
            $di->set('polus:response', $di->lazyNew('Zend\Diactoros\Response'));
        }

        if (!$di->has('polus:dispatch_resolver')) {
            $di->set('polus:dispatch_resolver', $di->lazyNew('Polus\DispatchResolver', [
                'resolver' => function ($cls) use ($di) {
                    return $di->newInstance($cls);
                },
            ]));
        }
        $di->types['Polus\DispatchResolverInterface'] = $di->lazyGet('polus:dispatch_resolver');
        if (!$di->has('polus:dispatcher')) {
            $di->set('polus:dispatcher', function () use ($di) {
                return function ($app) use ($di) {
                    return $di->newInstance('Polus\Dispatcher', [
                        'app' => $app,
                    ]);
                };
            });
        }

        if (!$di->has('polus:request')) {
            $di->set('polus:request', RequestFactory::fromGlobals());
        }
        if (!$di->has('polus:router_container')) {
            $di->set('polus:router_container', function () use ($di) {
                $routerContainer = $di->newInstance('Aura\Router\RouterContainer');
                $routerContainer->setRouteFactory(function () {
                    return new Route();
                });
                $routerContainer->getRuleIterator()->set([
                    new Rule\Secure(),
                    new Rule\Host(),
                    new AliasRule(),
                    new Rule\Allows(),
                    new Rule\Accepts(),
                ]);

                return $routerContainer;
            });
        }
    }
}
