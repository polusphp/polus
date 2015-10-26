<?php

namespace Polus\Controller;

use Polus\Traits\ResponseTrait;

class Error
{
    use ResponseTrait;

    public function handle($route, $exception = false)
    {
        $this->setContentType('application/json');
        $this->setResponseBody([
            'status'=>'error',
            'data'=>[
                'message'=>$exception?$exception->getMessage():'Generic error #1'
            ]
        ]);

        return $this->response;
    }
}
