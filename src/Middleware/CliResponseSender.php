<?php

namespace Polus\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Relay\Middleware\ResponseSender;

class CliResponseSender extends ResponseSender
{
    /**
     *
     * Sends the PSR-7 Response.
     *
     * @param Request $request The HTTP request.
     * @param Response $response The HTTP response.
     * @param callable $next The next middleware in the queue.
     *
     * @return Response
     */
    public function __invoke(Request $request, Response $response, callable $next)
    {
        $response = $next($request, $response);
        $this->sendBody($response);
        return $response;
    }
}
