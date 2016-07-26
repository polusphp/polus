<?php

namespace Polus\Middleware;

use Franzl\Middleware\Whoops\FormatNegotiator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\JsonResponse;

class ProductionErrorHandler
{
    protected $errorTexts = [
        'html' => '<!DOCTYPE html><html lang="en"><head> <meta charset="UTF-8"> <meta name="viewport" content="width=device-width"> <title>Something is not quite right.</title> <style> body { text-align: center; font-family: helvetica, arial, sans-serif; color: #999; } p { line-height: 1.6; } .image { padding: 2rem 2rem 0; } a:link, a:visited { color: #3a8bbb; } h1 { margin-bottom: 0; } h3 { margin-top: .5rem; } svg { width: 300px; max-width: 100%; fill: #aaa; } </style></head><body> <div class="image"> <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Capa_1" x="0px" y="0px" viewBox="0 0 60 60" xml:space="preserve"> <g> <g> <path d="M59,5H1C0.4,5,0,5.4,0,6v42c0,0.6,0.4,1,1,1h21v8h-3v2h4h14h4v-2h-3v-8h21c0.6,0,1-0.4,1-1V6C60,5.4,59.6,5,59,5z M36,57H24v-8h12V57z M58,47H37H23H2V7h56V47z" ></path> <path d="M5,45h50c0.6,0,1-0.4,1-1V10c0-0.6-0.4-1-1-1H5c-0.6,0-1,0.4-1,1v34C4,44.6,4.4,45,5,45z M6,11h48v32H6V11z"></path> <path d="M20.8,14h-3.6C12.7,14,9,17.7,9,22.2V40h2V22.2c0-3.4,2.8-6.2,6.2-6.2h3.6c3.4,0,6.2,2.8,6.2,6.2V40h2V22.2C29,17.7,25.3,14,20.8,14z" ></path> <rect x="13" y="38" width="2" height="2"></rect> <rect x="16" y="38" width="2" height="2"></rect> <rect x="20" y="38" width="2" height="2"></rect> <rect x="23" y="38" width="2" height="2"></rect> <polygon points="21,24 22,24 22,30 24,30 24,24 25,24 25,22 21,22"></polygon> <rect x="1" y="1" width="2" height="2"></rect> <rect x="5" y="1" width="2" height="2"></rect> <rect x="9" y="1" width="2" height="2"></rect> <rect x="13" y="1" width="2" height="2"></rect> <rect x="17" y="1" width="2" height="2"></rect> <rect x="21" y="1" width="2" height="2"></rect> <rect x="25" y="1" width="2" height="2"></rect> <rect x="29" y="1" width="2" height="2"></rect> <rect x="33" y="1" width="2" height="2"></rect> <rect x="37" y="1" width="2" height="2"></rect> <rect x="41" y="1" width="2" height="2"></rect> <rect x="45" y="1" width="2" height="2"></rect> <rect x="49" y="1" width="2" height="2"></rect> <rect x="53" y="1" width="2" height="2"></rect> <rect x="57" y="1" width="2" height="2"></rect> <polygon points="16,30 16,24 17,24 17,22 13,22 13,24 14,24 14,30"></polygon> <path d="M42,19c-5,0-9,4-9,9s4,9,9,9s9-4,9-9S47,19,42,19z M42,35c-3.9,0-7-3.1-7-7s3.1-7,7-7s7,3.1,7,7S45.9,35,42,35z"></path> <rect x="41" y="23" width="2" height="6"></rect> <rect x="41" y="31" width="2" height="2"></rect> </g> </g> </svg> </div> <div> <h1>Something is not quite right</h1> <h3>We hope to solve it shortly.</h3> <p> <a href="/">Click here to visit our main page</a>. <br/> If the problem presistes please contact admin; </p> </div></body></html>',
        'txt' => "An error occurred\n------------------\n\nPlease try agin later.\n\nIf the problem presists Please contact admin\n",
        'json' => '{"status":"error","message":"An error occurred. Please try agin later. If the problem presists Please contact admin"}',
    ];

    /**
     * Execute the middleware.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @param callable               $next
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        try {
            return $next($request, $response);
        } catch (\Exception $e) {
            if (php_sapi_name() === 'cli') {
                return new HtmlResponse($this->errorTexts['txt']);
            }
            $format = FormatNegotiator::getPreferredFormat($request);
            if (isset($this->errorTexts[$format])) {
                $text = $this->errorTexts[$format];
            } else {
                $text = $this->errorTexts['txt'];
            }
            if ($format === 'json') {
                return new JsonResponse($text, 500);
            } else {
                return new HtmlResponse($text, 500);
            }
        }
        return $response;
    }
}
