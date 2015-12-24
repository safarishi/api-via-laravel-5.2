<?php

namespace App\Exceptions;

use Lang;
use Exception;
use Illuminate\Http\JsonResponse;
use League\OAuth2\Server\Exception\OAuthException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Foundation\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $e
     * @return void
     */
    public function report(Exception $e)
    {
        return parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $e
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $e)
    {
        if ($e instanceof ApiException) {
            return $this->customApiExceptionHandler($e);
        }

        if ($e instanceof OAuthException) {
            return $this->customOAuthExceptionHandler($e);
        }

        return parent::render($request, $e);
    }

    private function customApiExceptionHandler($e)
    {
        $res = [
            'error' => $e->errorType,
            'error_description' => $e->getMessage()
        ];

        if ($code = $e->getCode()) {
            $res['error_code'] = $code;
        }
        if ($uri = $e->errorUri) {
            $res['error_uri'] = $uri;
        }

        return new JsonResponse($res, $e->httpStatusCode, $e->getHttpHeaders());
    }

    private function customOAuthExceptionHandler($e)
    {
        $attrs = [];

        $errorType    = $e->errorType;
        $errorMessage = $e->getMessage();

        if ($errorType == 'invalid_request') {
            if ($errorMessage == 'The refresh token is invalid.') {
                $errorType = 'invalid_refresh_token';
            } else {
                preg_match('/"(.*?)"/', $errorMessage, $matches);
                $parameter = $matches[1];
                $attrs['parameter'] = $parameter;
            }
        }

        $message = Lang::get('oauth.'.$errorType, $attrs);
        $message = starts_with($message, 'oauth.') ? $errorMessage : $message;

        return new JsonResponse([
                'error' => $e->errorType,
                'error_description' => $message,
            ],
            $e->httpStatusCode,
            $e->getHttpHeaders()
        );
    }

}
