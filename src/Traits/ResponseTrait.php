<?php
namespace Polus\Traits;

use Psr\Http\Message\ResponseInterface;

trait ResponseTrait
{
    protected $response = false;

    public function setResponse(ResponseInterface $response)
    {
        $this->response = $response;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function setResponseBody($data)
    {
        if (!is_string($data)) {
            $data = json_encode($data, \JSON_PRETTY_PRINT);
        }
        $this->response->getBody()->write($data);
        return $this;
    }
    public function setResponseHeader($key, $value)
    {
        $this->response = $this->response->withHeader($key, $value);
        return $this;
    }
    
    public function setContentType($type)
    {
        return $this->setResponseHeader('Content-Type', $type);
    }
}
