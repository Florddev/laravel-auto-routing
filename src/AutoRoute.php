<?php

namespace Florddev\LaravelAutoRouting;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use Symfony\Component\Finder\Finder;
use Florddev\LaravelAutoRouting\Attributes\{HttpGet, HttpPost, HttpPut, HttpPatch, HttpDelete, ControllerRoute};

class AutoRoute
{
    protected $httpMethodAttributes = [
        HttpGet::class => 'get',
        HttpPost::class => 'post',
        HttpPut::class => 'put',
        HttpPatch::class => 'patch',
        HttpDelete::class => 'delete',
    ];

    protected $exceptions = [];
    protected $baseDirectory;
    protected $initalOptions = [];
    protected $isDirectoryRouting = false;

    public function register($prefix, $controllerOrDirectory, $options = [])
    {
        $this->initalOptions = $options;
        if (isset($options['except'])) {
            $this->exceptions = is_array($options['except']) ? $options['except'] : [$options['except']];
        }

        if (is_string($controllerOrDirectory) && is_dir($controllerOrDirectory)) {
            $this->baseDirectory = rtrim($controllerOrDirectory, '/');
            $result = $this->registerDirectory($prefix, $controllerOrDirectory, $options);
            $this->exceptions = []; // Reset exceptions after registration
            return $result;
        } else {
            $this->registerController($prefix, $controllerOrDirectory, $options);
            $this->exceptions = []; // Reset exceptions after registration
            return $this;
        }
    }

    public function except($exceptions)
    {
        $this->exceptions = is_array($exceptions) ? $exceptions : [$exceptions];
        return $this;
    }

    protected function registerDirectory($prefix, $directory, $options)
    {
        $this->isDirectoryRouting = true;
        $baseNamespace = $this->getNamespaceFromDirectory($directory);
        $finder = new Finder();
        $finder->files()->in($directory)->name('*Controller.php');

        foreach ($finder as $file) {
            $relativePath = $this->getRelativePath($file->getPathname());
            $subNamespace = str_replace('/', '\\', $relativePath);
            $subNamespace = rtrim($subNamespace, '.php');

            $controllerClass = $baseNamespace . ($subNamespace ? '\\' . ltrim($subNamespace, '\\') : '');
            $controllerClass = rtrim($controllerClass, '\\');

            $subPrefix = $this->getSubPrefixFromFilePath($file->getPathname(), $directory);
            $fullPrefix = $prefix . ($subPrefix ? '/' . $subPrefix : '');

            if(isset($this->initalOptions['prefix'])){
                $fullPrefix = '/' . $this->initalOptions['prefix'] . '/' . trim($fullPrefix, '/');
                unset($options['prefix']);
            }

            $className = class_basename($controllerClass);
            $className = str_replace('Controller', '', $className);
            $className = $this->convertToKebabCase($className);

            if (!$this->isExcluded($relativePath, $className)) {
                $this->registerController($fullPrefix, $controllerClass, $options);
            }
        }

        return $this;
    }

    protected function isExcluded($relativePath, $controllerName)
    {
        $relativePath = trim($relativePath, '/');
        foreach ($this->exceptions as $exception) {
            $exception = trim($exception, '/');

            // Vérification pour les chemins de dossier
            if (Str::startsWith($relativePath, $exception)) {
                return true;
            }
            // Vérification pour les noms de contrôleur
            else {
                $controllerFileName = $controllerName . 'Controller.php';
                if (strcasecmp($exception, $controllerName) === 0 ||
                    strcasecmp($exception, $controllerFileName) === 0 ||
                    strcasecmp($exception, $relativePath) === 0) {
                    return true;
                }
            }
        }
        return false;
    }

    protected function getRelativePath($filePath)
    {
        return ltrim(Str::after($filePath, $this->baseDirectory), '/\\');
    }

    protected function getSubPrefixFromFilePath($filePath, $baseDirectory)
    {
        $relativePath = $this->getRelativePath($filePath);
        $parts = explode('/', $relativePath);
        array_pop($parts); // Remove the filename
        return implode('/', array_map([$this, 'convertToKebabCase'], $parts));
    }

    protected function getNamespaceFromDirectory($directory)
    {
        $composerJson = json_decode(file_get_contents(base_path('composer.json')), true);
        $psr4 = $composerJson['autoload']['psr-4'] ?? [];

        $directory = realpath($directory);

        foreach ($psr4 as $namespace => $path) {
            $path = realpath(base_path($path));
            if ($path && strpos($directory, $path) === 0) {
                $relativeDir = substr($directory, strlen($path));
                return rtrim($namespace, '\\') . '\\' . str_replace('/', '\\', trim($relativeDir, '/'));
            }
        }

        throw new \Exception("Unable to determine namespace for directory: $directory");
    }

    protected function registerController($prefix, $controller, $options = [])
    {
        $reflectionClass = new ReflectionClass($controller);
        $controllerName = $this->getControllerName($controller);

        $controllerRouteAttribute = $this->getControllerRouteAttribute($reflectionClass);

        $groupAttributes = [];

        $hasCustomPrefix = false;
        if(isset($controllerRouteAttribute->options["prefix"])){
            $prefix = $controllerRouteAttribute->options["prefix"];
            $hasCustomPrefix = true;
        }

        $prefix = trim($prefix, "/");
        if ($prefix) {
            $groupAttributes['prefix'] = $prefix;
        }

        if ($controllerRouteAttribute) {
            foreach ($controllerRouteAttribute->options as $key => $value) {
                if ($key !== 'prefix') {  // Nous avons déjà traité le prefix
                    $groupAttributes[$key] = $value;
                }
            }
        }

        // Fusionner les options globales avec les attributs du groupe
        $groupAttributes = array_merge($groupAttributes, $options);

        if(isset($controllerRouteAttribute->options["name"])){
            $prefix = "";
        }

        // Si aucun préfixe personnalisé n'a été défini, utiliser le nom du contrôleur
        if (!$hasCustomPrefix && $this->isDirectoryRouting) {
            $controllerPrefix = $this->convertToKebabCase($controllerName);
            $groupAttributes['prefix'] = ($groupAttributes['prefix'] ?? '') . '/' . $controllerPrefix;
        }

        Route::group($groupAttributes, function () use ($reflectionClass, $controllerName, $controller, $controllerRouteAttribute, $prefix, $hasCustomPrefix) {
            foreach ($reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                $this->registerRoute($prefix, $controller, $controllerName, $method, $controllerRouteAttribute, $hasCustomPrefix);
            }
        });
    }

    protected function getControllerRouteAttribute(ReflectionClass $reflectionClass)
    {
        $attributes = $reflectionClass->getAttributes(ControllerRoute::class);
        return !empty($attributes) ? $attributes[0]->newInstance() : null;
    }

    protected function registerRoute($prefix, $controller, $controllerName, ReflectionMethod $method, $controllerRouteAttribute = null, $hasCustomPrefix = false)
    {
        $httpMethodAttribute = $this->getHttpMethodAttribute($method);

        if (!$httpMethodAttribute) {
            return; // Skip methods without HTTP method attributes
        }

        $httpMethod = $this->httpMethodAttributes[get_class($httpMethodAttribute)];

        $options = $httpMethodAttribute->options;
        $url = $options['url'] ?? $this->convertToKebabCase($method->getName());

        $namePrefix = empty($controllerRouteAttribute?->options['name']) ? ($prefix . '.') : $prefix;
        $name = $options['name'] ?? $this->generateRouteName($namePrefix, $method->getName());

        if ($method->getName() === 'index' && !isset($options['url'])) {
            $url = '';
        } else if(!isset($options['url'])) {
            $url = $this->buildUrlWithParameters($url, $method);
        }

        // Ne pas ajouter le nom du contrôleur si un préfixe personnalisé a été défini ou si l'URL est déjà définie
        if (!$hasCustomPrefix && !isset($options['url'])) {
            $url = trim($url, '/');
        }

        $route = Route::{$httpMethod}($url, [$controller, $method->getName()]);

        // Appliquer le nom de route du contrôleur s'il existe
        if ($controllerRouteAttribute && isset($controllerRouteAttribute->options['name'])) {
            $name = $controllerRouteAttribute->options['name'] . $name;
        }
        $route->name($name);

        // Appliquer les middleware
        $middlewares = [];
        if ($controllerRouteAttribute && isset($controllerRouteAttribute->options['middleware'])) {
            $middlewares = array_merge($middlewares, (array)$controllerRouteAttribute->options['middleware']);
        }
        if (isset($options['middleware'])) {
            $middlewares = array_merge($middlewares, (array)$options['middleware']);
        }
        if (!empty($middlewares)) {
            $route->middleware($middlewares);
        }

        // Appliquer les autres options de route
        foreach ($options as $key => $value) {
            if (method_exists($route, $key) && !in_array($key, ['url', 'name', 'middleware'])) {
                $route->$key($value);
            }
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

        foreach ($parameters as $key=>$param){
            if($param->getType() == Request::class) unset($parameters[$key]);
        }

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
        return Str::kebab($string);
        //return strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $string));
    }

    protected function getControllerName($controller)
    {
        $reflection = new ReflectionClass($controller);
        return str_replace('Controller', '', $reflection->getShortName());
    }

    protected function generateRouteName($prefix, $method)
    {
        return str_replace("/", '.', $prefix) . $this->convertToSnakeCase($method);
    }

    protected function convertToSnakeCase($string)
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $string));
    }
}
