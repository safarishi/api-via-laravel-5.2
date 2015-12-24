<?php

namespace App\Http\Middleware;

use Route;
use Closure;
use ReflectionClass;
use App\Exceptions\InvalidRequestException;
use Illuminate\Validation\Factory as Validator;

class ValidationMiddleware
{
    const CONTROLLER_NAMESPACE = 'App\Http\Controllers\\';

    /**
     * Validator
     *
     * @var \Illuminate\Validation\Factory
     */
    protected $validator;

    /**
     * Validator rules
     *
     * @var string|array
     */
    protected $rules = [];

    /**
     * the property name used in auto validate mode
     *
     * @var string
     */
    public $propertyName = '_validate';

    public $controllerName = '';

    public $actionName = '';

    /**
     * validator constuct
     *
     * @param Validator $validator Validator
     */
    public function __construct(Validator $validator)
    {
        $this->validator = $validator;
    }

    public function handle($request, Closure $next)
    {
        $this->beforeMiddleware(Route::getCurrentRoute(), $request);

        return $next($request);
    }

    /**
     * Run the validation before middleware
     *
     * @param mixed $route
     * @param mixed $request
     * @return void
     */
    protected function beforeMiddleware($route, $request)
    {
        $str = $route->getAction()['uses'];
        $pos = strrpos($str, '\\');

        $routeParam = explode('@', substr($str, $pos + 1));

        $this->controllerName = $routeParam[0];
        $this->actionName = $routeParam[1];
        $this->setControllerRule();
        // get and check the validation rules used in this request
        if (! $rules = $this->getRules()) {
            return;
        }

        $validator = $this->validator->make($request->all(), $rules);
        if ($validator->fails()) {
            $messages = $validator->messages()->all();
            throw new InvalidRequestException($messages);
        }
    }

    private function setControllerRule()
    {
        $this->controllerName = self::CONTROLLER_NAMESPACE.$this->controllerName;

        // check controller is exists
        if (! class_exists($this->controllerName)) {
            return;
        }
        // use reflection class to get the property value
        $controllerRlection = new ReflectionClass($this->controllerName);
        if (! $controllerRlection->hasProperty($this->propertyName)) {
            return;
        }

        $prop = $controllerRlection->getProperty($this->propertyName);
        $prop->setAccessible(true);
        $controllerRules = $prop->getValue();

        if (! array_key_exists($this->actionName, $controllerRules)) {
            return;
        }

        $this->rules[$this->controllerName] = $controllerRules;
    }

    /**
     * get needed rules
     *
     * @return mixed validator rules
     */
    private function getRules()
    {
        return ! empty($this->rules) ?
            $this->rules[$this->controllerName][$this->actionName] : null;
    }
}