<?php

use Illuminate\Container\Container;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\JsonResponse;
use Illuminate\Translation\FileLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;

function app($abstract = null, array $parameters = [])
{
    if (is_null($abstract)) {
        return Container::getInstance();
    }

    return Container::getInstance()->make($abstract, $parameters);
}

function config($key = null, $default = null)
{
    if (is_null($key)) {
        return app('config');
    }

    if (is_array($key)) {
        return app('config')->set($key);
    }

    return app('config')->get($key, $default);
}

function request($key = null, $default = null)
{
    if (is_null($key)) {
        return app('request');
    }

    if (is_array($key)) {
        return app('request')->only($key);
    }

    $value = app('request')->__get($key);

    return is_null($value) ? value($default) : $value;
}

function response($content = '', $status = 200, array $headers = [])
{
    $res = app('response');

    $res = new $res;

    $res->setContent($content);
    $res->setStatusCode($status);

    return $res->send();
}

function jsonResponse($data, $status = 200, $headers = [], $options = 0)
{
    $res = new JsonResponse($data, $status, $headers, $options);

    $res->send();

    die();
}

function view($view = null, $data = [], $mergeData = [])
{
    $factory = app(ViewFactory::class);

    if (func_num_args() === 0) {
        return $factory;
    }

    echo $factory->make($view, $data, $mergeData);
}

function trans($text, $textDomain = TEXT_DOMAIN)
{
    return translate($text, $textDomain);
}

function validate($data, $rules)
{
    $loader = new FileLoader(new Filesystem, 'lang');
    $translator = new Translator($loader, 'en');
    $validation = new Factory($translator, app());

    $errors = null;

    $validator = $validation->make($data, $rules);

    if ($validator->fails()) {
        $errors = $validator->errors();

        return jsonResponse($errors, 403);
    }

    return $data;
}

function addJsVariable(string $name, $data) : void
{
    app()->addJsVariable($name, $data);
}

function getJsVariables() : array
{
    return app()->getJsVariables();
}

function base_path($path = '')
{
    return app()->basePath($path);
}

function public_path($path = '')
{
    return app()->publicPath($path);
}

function resource_path($path = '')
{
    return app()->resourcePath($path);
}

function database_path($path = '')
{
    return app()->databasePath($path);
}

function asset(string $path)
{
    return app()->asset($path);
}

function vite($name)
{
    $manifest = app()->publicPath('dist/manifest.json');

    $manifest = file_get_contents($manifest);

    $manifest = json_decode($manifest, true);

    if ($name === 'app.js') {
        return '/dist/'.$manifest['resources/app.js']['file'];
    } elseif ($name === 'app.css') {
        return '/dist/'.$manifest['resources/app.js']['css'][0];
    }
}
