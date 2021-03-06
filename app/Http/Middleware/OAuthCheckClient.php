<?php

namespace App\Http\Middleware;

use DB;
use Closure;
use App\Exceptions\UnauthorizedClientException;
use League\OAuth2\Server\Exception\InvalidRequestException;

class OAuthCheckClient
{
    public function handle($request, Closure $next)
    {
        $clientId = request('client_id', null);
        if (is_null($clientId)) {
            throw new InvalidRequestException('client_id');
        }

        $clientSecret = request('client_secret', null);
        if (is_null($clientSecret)) {
            throw new InvalidRequestException('client_secret');
        }

        $client = DB::connection('mysql')->table('oauth_clients')
            ->where('id', '=', $clientId)
            ->where('secret', '=', $clientSecret)
            ->exists();
        if (! $client) {
            throw new UnauthorizedClientException;
        }

        return $next($request);
    }
}