<?php

namespace App\Http\Controllers;

class OAuthController extends ApiController
{
    public function postAccessToken()
    {
        $this->authorizer->issueAccessToken();
    }
}