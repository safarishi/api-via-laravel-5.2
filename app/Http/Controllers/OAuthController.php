<?php

namespace App\Http\Controllers;

class OAuthController extends ApiController
{
    public function postAccessToken()
    {
        return $this->authorizer->issueAccessToken();
    }
}
