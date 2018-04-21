<?php

namespace Application;

use Silex\ControllerResolver as BaseControllerResolver;
use Symfony\Component\HttpFoundation\Request;

class ControllerResolver extends BaseControllerResolver
{

    protected function doGetArguments(Request $request, $controller, array $parameters)
    {
        // Add get and post arguments to be available as controller arguments
        foreach (array_merge($request->query->all(), $request->request->all()) as $key => $value) {
            if (!$request->attributes->has($key)) {
                $request->attributes->set($key, $value);
            }
        }

        return parent::doGetArguments($request, $controller, $parameters);
    }
}
