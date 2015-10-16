<?php

namespace Polus;

use Psr\Http\Message\ResponseInterface;

class Sender
{
    public function send(ResponseInterface $response)
    {
        $this->sendStatus($response);
        $this->sendHeaders($response);
        $this->sendBody($response);
    }
    protected function sendStatus($response)
    {
        $version = $response->getProtocolVersion();
        $status = $response->getStatusCode();
        $phrase = $response->getReasonPhrase();
        header("HTTP/{$version} {$status} {$phrase}");
    }
    protected function sendHeaders(ResponseInterface $response)
    {
        foreach ($response->getHeaders() as $name => $values) {
            $this->sendHeader($name, $values);
        }
    }
    protected function sendHeader($name, $values)
    {
        $name = str_replace('-', ' ', $name);
        $name = ucwords($name);
        $name = str_replace(' ', '-', $name);
        foreach ($values as $value) {
            header("{$name}: {$value}", false);
        }
    }
    protected function sendBody(ResponseInterface $response)
    {
        while (ob_get_level()) {
            ob_end_flush();
        }
        echo $response->getBody();
    }
}
