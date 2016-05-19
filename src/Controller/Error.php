<?php

namespace Polus\Controller;

use Aura\Router\Exception\RouteNotFound;
use Aura\Router\Route;
use Polus\Traits\ResponseTrait;

class Error
{
    use ResponseTrait;
    protected $errorRoutes = [
        400 => 'error.400',
        401 => 'error.401',
        403 => 'error.403',
        404 => 'error.404',
        405 => 'error.405',
        406 => 'error.406',
        500 => 'error.500',
    ];
    protected $internalMap = [
        404 => 'handle404',
        405 => 'handle405',
        406 => 'handle406',
        500 => 'handle500',
        400 => 'handle500',
    ];
    protected $map;
    protected $app;
    protected $request;
    protected $isXhr = true;

    public function __construct($route_map, $app, $request)
    {
        $this->map = $route_map;
        $this->app = $app;
        $this->request = $request;
        if ($this->request->getHeader('HTTP_X_REQUESTED_WITH') == 'xmlhttprequest') {
            $this->isXhr = true;
        }
    }

    protected function getRoute($code, $internal = false)
    {
        if ($internal) {
            return [$this, $this->internalMap[$code]];
        }
        try {
            $routeName = $this->errorRoutes[$code];
            if ($routeName) {
                return $this->map->getRoute($routeName);
            }
            $code = 500;
        } catch (RouteNotFound $rnf) {
            if (!isset($this->internalMap[$code])) {
                $code = 500;
            }
        }
        return [$this, $this->internalMap[$code]];
    }

    public function dispatch($action, $data)
    {
        if (isset($data['internal']) && $data['internal']) {
            $route = $this->getRoute(500, true);
        } else {
            switch ($action) {
                case 'action_exception':
                    $route = $this->getRoute($data['exception']->getCode());
                    break;
                case 'no_action':
                    $route = $this->getRoute(500);
                    break;
                case 'no_match':
                    $route = $this->handleNoMatch($data['rule'], $data['route']);
                    break;
                default:
                    $route = $this->getRoute(500);
                    break;
            }
        }
        if ($route instanceof Route) {
            if (isset($data['route'])) {
                $data['error_route'] = $data['route'];
                unset($data['route']);
            }
            $route->internal = true;
            $route->attributes($data);
            $this->app->dispatch($route);
        } elseif (method_exists($this, $route[1])) {
            $method = $route[1];
            $this->app->sender->send($this->$method($data));
        }
    }

    protected function handleNoMatch($failedRule, $route)
    {
        switch ($failedRule) {
            case 'Aura\Router\Rule\Allows':
                return $this->getRoute(405);
            case 'Aura\Router\Rule\Accepts':
                return $this->getRoute(406);
            case 'Polus\Router\AliasRule':
                return $this->getRoute(404);
        }
        return $this->getRoute(400);
    }

    protected function handle404($info)
    {
        $data = [
            'message' => 'Page not found',
        ];
        if ($this->app->debug()) {
            $data['route'] = [
                'name' => $info['route']->name,
                'path' => $info['route']->path,
                'host' => $info['route']->host,
                'allows' => $info['route']->allows,
                'accepts' => $info['route']->accepts,
            ];
        }
        if ($this->isXhr) {
            $this->setContentType('application/json');
            $this->setResponseBody([
                'status' => 'error',
                'code' => 404,
                'data' => $data,
            ]);
        }
        return $this->response;
    }

    protected function handle405($info)
    {
        $data = [
            'message' => 'Method Not Allowed',
        ];
        if ($this->app->debug()) {
            $data['route'] = [
                'name' => $info['route']->name,
                'allows' => $info['route']->allows,
            ];
        }
        if ($this->isXhr) {
            $this->setContentType('application/json');
            $this->setResponseBody([
                'status' => 'error',
                'code' => 405,
                'data' => $data,
            ]);
        }
        return $this->response;
    }

    protected function handle406($info)
    {
        $data = [
            'message' => 'Not Acceptable',
        ];
        if ($this->app->debug()) {
            $data['route'] = [
                'name' => $info['route']->name,
                'accepts' => $info['route']->accepts,
            ];
        }
        if ($this->isXhr) {
            $this->setContentType('application/json');
            $this->setResponseBody([
                'status' => 'error',
                'code' => 406,
                'data' => $data,
            ]);
        }
        return $this->response;
    }

    protected function handle500($info)
    {
        $exception = $info['exception'];
        if ($exception) {
            $message = get_class($exception) . ' #' . $exception->getCode();
            $trace = $exception->getTrace();
            $in = $trace[0]['class'] . '::' . $trace[0]['function'];
            $message .= ' in ' . $in;
        } elseif ($info['rule']) {
            $rule = $info['rule'];
            $message = 'Error in router matching';
        }

        if ($this->app->debug()) {
            if (isset($rule)) {
                $data['rule'] = $rule;
            }
            if ($exception) {
                $data['exception'] = [
                    'trace' => $exception->getTrace(),
                    'code' => $exception->getCode(),
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                ];
            }
            if ($info['route']) {
                $data['route'] = [
                    'name' => $info['route']->name,
                    'path' => $info['route']->path,
                    'host' => $info['route']->host,
                    'allows' => $info['route']->allows,
                    'accepts' => $info['route']->accepts,
                ];
            }
        }
        $data['message'] = $message;
        if ($this->isXhr) {
            $this->setContentType('application/json');
            $this->setResponseBody([
                'status' => 'error',
                'code' => 500,
                'data' => $data,
            ]);
        }
        return $this->response;
    }
}
