<?php

namespace App\Http\Controllers;

class CommonController extends ApiController
{
    /**
     * 根据 Access Token 获取用户 ID
     *
     * @return string
     */
    protected function getOwnerId()
    {
        $this->authorizer->validateAccessToken();

        return $this->authorizer->getResourceOwnerId();
    }
}
