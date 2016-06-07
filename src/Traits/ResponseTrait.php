<?php
namespace Polus\Traits;

use Psr\Http\Message\ResponseInterface;

trait ResponseTrait
{
    /**
     * @var ResponseInterface
     */
    protected $response;

    /**
     * @param ResponseInterface $response
     */
    public function setResponse(ResponseInterface $response)
    {
        $this->response = $response;
    }

    /**
     * @return ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Set response body if is not string json_encode
     *
     * @param mixed $data
     * @return $this
     */
    public function setResponseBody($data)
    {
        if (!is_string($data)) {
            $data = json_encode($data, \JSON_PRETTY_PRINT);
        }
        $this->response->getBody()->write($data);
        return $this;
    }

    /**
     * @param string $key   header-name
     * @param string $value header-value
     * @return $this
     */
    public function setResponseHeader($key, $value)
    {
        $this->response = $this->response->withHeader($key, $value);
        return $this;
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setContentType($type)
    {
        return $this->setResponseHeader('Content-Type', $type);
    }
}
