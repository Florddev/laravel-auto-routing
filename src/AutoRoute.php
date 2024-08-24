<?php

namespace Florddev\LaravelAutoRouting;

use Illuminate\Support\Facades\Route;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use Florddev\LaravelAutoRouting\Attributes\{HttpGet, HttpPost, HttpPut, HttpPatch, HttpDelete};

class AutoRoute
{
    protected $httpMethodAttributes = [
        HttpGet::class => 'get',
        HttpPost::class => 'post',
        HttpPut::class => 'put',
        HttpPatch::class => 'patch',
        HttpDelete::class => 'delete',
    ];

    public function register($prefix, $controller, $options = [])
    {
        $reflectionClass = new ReflectionClass($controller);
        $controllerName = $this->getControllerName($controller);

        $routeGroup = $prefix ? Route::prefix($prefix) : Route::getFacadeRoot();

        if (isset($options['middleware'])) {
            $routeGroup->middleware($options['middleware']);
        }

        if (isset($options['namespace'])) {
            $routeGroup->namespace($options['namespace']);
        }

        if (isset($options['domain'])) {
            $routeGroup->domain($options['domain']);
        }

        if (isset($options['name'])) {
            $routeGroup->name($options['name']);
        }

        $routeGroup->group(function () use ($reflectionClass, $controllerName, $controller) {
            foreach ($reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                $this->registerRoute($controller, $controllerName, $method);
            }
        });
    }

    protected function registerRoute($controller, $controllerName, ReflectionMethod $method)
    {
        $httpMethodAttribute = $this->getHttpMethodAttribute($method);

        $httpMethod = $httpMethodAttribute
            ? $this->httpMethodAttributes[get_class($httpMethodAttribute)]
            : 'any';

        $baseUrl = $httpMethodAttribute?->url ?? $this->convertToKebabCase($method->getName());
        $name = $httpMethodAttribute?->name ?? $this->generateRouteName($controllerName, $method->getName());

        $url = $this->buildUrlWithParameters($baseUrl, $method);

        $route = Route::{$httpMethod}($url, [$controller, $method->getName()]);
        $route->name($name);

        if ($httpMethodAttribute && $httpMethodAttribute->middleware) {
            $route->middleware($httpMethodAttribute->middleware);
        }
    }

    protected function getHttpMethodAttribute(ReflectionMethod $method)
    {
        foreach ($this->httpMethodAttributes as $attributeClass => $httpMethod) {
            $attributes = $method->getAttributes($attributeClass);
            if (!empty($attributes)) {
                return $attributes[0]->newInstance();
            }
        }
        return null;
    }

    protected function buildUrlWithParameters($baseUrl, ReflectionMethod $method)
    {
        $parameters = $method->getParameters();
        $parameterString = $this->buildParameterString($parameters);
        return trim($baseUrl . '/' . $parameterString, '/');
    }

    protected function buildParameterString(array $parameters)
    {
        return implode('/', array_map(function (ReflectionParameter $param) {
            $paramStr = '{' . $param->getName();
            if ($param->isOptional()) {
                $paramStr .= '?';
            }
            return $paramStr . '}';
        }, array_filter($parameters, function (ReflectionParameter $param) {
            return !$param->getType() || !$param->getType()->isBuiltin() || $param->getType()->getName() !== 'Illuminate\Http\Request';
        })));
    }

    protected function convertToKebabCase($string)
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $string));
    }

    protected function getControllerName($controller)
    {
        $reflection = new ReflectionClass($controller);
        return str_replace('Controller', '', $reflection->getShortName());
    }

    protected function generateRouteName($controller, $method)
    {
        return strtolower($controller) . '.' . $this->convertToSnakeCase($method);
    }

    protected function convertToSnakeCase($string)
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $string));
    }
}
