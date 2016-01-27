<?php

namespace App\Http\Controllers;

class CommonController extends ApiController
{
    protected function getOwnerId()
    {
        $this->authorizer->validateAccessToken();

        return $this->authorizer->getResourceOwnerId();
    }
}
