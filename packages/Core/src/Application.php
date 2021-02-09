<?php

namespace WpNext\Core;

use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpFoundation\Response;
use WpNext\Routing\Router;
use WpNext\Support\Facades\Action;
use WpNext\Support\Facades\Filter;

class Application extends Container
{
    const VERSION = '0.0.1';

    protected $basePath;

    protected $hasBeenBootstrapped = false;

    protected $booted = false;

    protected $bootingCallbacks = [];

    protected $bootedCallbacks = [];

    protected $terminatingCallbacks = [];

    protected $serviceProviders = [];

    protected $loadedProviders = [];

    protected $jsVariables = [];

    public function __construct(string $basePath)
    {
        $this->setBasePath($basePath);

        Facade::setFacadeApplication($this);

        $this->bindPathsInContainer();
        $this->registerBaseBindings();
        $this->registerCoreContainerAliases();
        $this->registerServiceProviders();

        $this->hasBeenBootstrapped = true;
    }

    public function hasBeenBootstrapped()
    {
        return $this->hasBeenBootstrapped;
    }

    public function asset($path)
    {
        return $this->get('url.assets').$path;
    }

    public function isHmrMode()
    {
        return file_exists($this->basePath('/hot'));
    }

    public function setBasePath($basePath)
    {
        $this->basePath = rtrim($basePath, '\/');
    }

    protected function registerBaseBindings()
    {
        static::setInstance($this);

        $this->instance('app', $this);

        $this->instance(Container::class, $this);
        $this->instance('response', Response::class);
        $this->instance('config', (new ConfigLoader)->loadConfig());

        $filesystem = new Filesystem;

        $this->bind('files', function ($app) use ($filesystem) {
            return $filesystem;
        });
    }

    protected function registerServiceProviders()
    {
        $providers = config('app.providers');

        if (empty($providers)) {
            return;
        }

        collect($providers)->each(function ($provider) {
            $this->register($instance = new $provider($this));
        });
    }

    public function register($provider, $force = false)
    {
        if (($registered = $this->getProvider($provider)) && ! $force) {
            return $registered;
        }

        if (is_string($provider)) {
            $provider = $this->resolveProvider($provider);
        }

        if (method_exists($provider, 'register')) {
            $provider->register();
        }

        $this->markAsRegistered($provider);

        return $provider;
    }

    protected function markAsRegistered($provider)
    {
        $this->serviceProviders[] = $provider;

        $this->loadedProviders[get_class($provider)] = true;
    }

    protected function bootProviders()
    {
        if ($this->isBooted()) {
            return;
        }

        $this->fireAppCallbacks($this->bootingCallbacks);

        array_walk($this->serviceProviders, function ($p) {
            $this->bootProvider($p);
        });

        $this->booted = true;

        $this->fireAppCallbacks($this->bootedCallbacks);
    }

    protected function fireAppCallbacks(array $callbacks)
    {
        foreach ($callbacks as $callback) {
            $callback($this);
        }
    }

    protected function bootProvider(ServiceProvider $provider)
    {
        $provider->callBootingCallbacks();

        if (method_exists($provider, 'boot')) {
            $this->call([$provider, 'boot']);
        }

        $provider->callBootedCallbacks();
    }

    public function getProvider($provider)
    {
        return array_values($this->getProviders($provider))[0] ?? null;
    }

    public function getProviders($provider)
    {
        $name = is_string($provider) ? $provider : get_class($provider);

        return Arr::where($this->serviceProviders, function ($value) use ($name) {
            return $value instanceof $name;
        });
    }

    public function resolveProvider($provider)
    {
        return new $provider($this);
    }

    public function isBooted()
    {
        return $this->booted;
    }

    public function loadTextDomain($path, $textDomain = TEXT_DOMAIN)
    {
        load_textdomain($textDomain, $path);
    }

    public function registerTemplates($templates)
    {
        Filter::add('theme_page_templates', function ($existingTemplates) use ($templates) {
            return $templates;
        });
    }

    protected function registerCoreContainerAliases()
    {
        $this->instance(\Illuminate\Contracts\Foundation\Application::class, $this);

        $this->bind(
            'Illuminate\Contracts\Foundation\Application',
            function () {
                return $this;
            }
        );

        foreach ([
            'ajax' => [
                \WpNext\Ajax\Ajax::class,
            ],
            'action' => [
                \WpNext\Hook\ActionBuilder::class,
            ],
            'filter' => [
                \WpNext\Hook\FilterBuilder::class,
            ],
            'app'                  => [self::class, \Illuminate\Contracts\Container\Container::class, \Illuminate\Contracts\Foundation\Application::class, \Psr\Container\ContainerInterface::class],
            'blade.compiler'       => [\Illuminate\View\Compilers\BladeCompiler::class],
            'cache'                => [\Illuminate\Cache\CacheManager::class, \Illuminate\Contracts\Cache\Factory::class],
            'cache.store'          => [\Illuminate\Cache\Repository::class, \Illuminate\Contracts\Cache\Repository::class, \Psr\SimpleCache\CacheInterface::class],
            'cache.psr6'           => [\Symfony\Component\Cache\Adapter\Psr16Adapter::class, \Symfony\Component\Cache\Adapter\AdapterInterface::class, \Psr\Cache\CacheItemPoolInterface::class],
            'config'               => [\Illuminate\Config\Repository::class, \Illuminate\Contracts\Config\Repository::class],
            'cookie'               => [\Illuminate\Cookie\CookieJar::class, \Illuminate\Contracts\Cookie\Factory::class, \Illuminate\Contracts\Cookie\QueueingFactory::class],
            'events'               => [\Illuminate\Events\Dispatcher::class, \Illuminate\Contracts\Events\Dispatcher::class],
            'files'                => [\Illuminate\Filesystem\Filesystem::class],
            'filesystem'           => [\Illuminate\Filesystem\FilesystemManager::class, \Illuminate\Contracts\Filesystem\Factory::class],
            'filesystem.disk'      => [\Illuminate\Contracts\Filesystem\Filesystem::class],
            'hash'                 => [\Illuminate\Hashing\HashManager::class],
            'hash.driver'          => [\Illuminate\Contracts\Hashing\Hasher::class],
            'translator'           => [\Illuminate\Translation\Translator::class, \Illuminate\Contracts\Translation\Translator::class],
            'log'                  => [\Illuminate\Log\LogManager::class, \Psr\Log\LoggerInterface::class],
            'mail.manager'         => [\Illuminate\Mail\MailManager::class, \Illuminate\Contracts\Mail\Factory::class],
            'mailer'               => [\Illuminate\Mail\Mailer::class, \Illuminate\Contracts\Mail\Mailer::class, \Illuminate\Contracts\Mail\MailQueue::class],
            'queue.failer'         => [\Illuminate\Queue\Failed\FailedJobProviderInterface::class],
            'redirect'             => [\Illuminate\Routing\Redirector::class],
            'redis'                => [\Illuminate\Redis\RedisManager::class, \Illuminate\Contracts\Redis\Factory::class],
            'redis.connection'     => [\Illuminate\Redis\Connections\Connection::class, \Illuminate\Contracts\Redis\Connection::class],
            'request'              => [\Illuminate\Http\Request::class, \Symfony\Component\HttpFoundation\Request::class],
            'route'                => [\Illuminate\Support\Facades\Route::class],
            'router'               => [\Illuminate\Routing\Router::class, \Illuminate\Contracts\Routing\Registrar::class, \Illuminate\Contracts\Routing\BindingRegistrar::class],
            'session'              => [\Illuminate\Session\SessionManager::class],
            'session.store'        => [\Illuminate\Session\Store::class, \Illuminate\Contracts\Session\Session::class],
            'url'                  => [\Illuminate\Routing\UrlGenerator::class, \Illuminate\Contracts\Routing\UrlGenerator::class],
            'validator'            => [\Illuminate\Validation\Factory::class, \Illuminate\Contracts\Validation\Factory::class],
            'view'                 => [\Illuminate\View\Factory::class, \Illuminate\Contracts\View\Factory::class],
        ] as $key => $aliases) {
            foreach ($aliases as $alias) {
                $this->alias($key, $alias);
            }
        }
    }

    protected function bindPathsInContainer()
    {
        // Core
        $this->instance('path', $this->path());
        // Base
        $this->instance('path.base', $this->basePath());
        // Resources
        $this->instance('path.resources', $this->resourcePath());
        // Public
        $this->instance('path.public', $this->publicPath());
        // Bootstrap
        $this->instance('path.bootstrap', $this->bootstrapPath());
        // Config
        $this->instance('path.config', $this->configPath());
        // Storage
        $this->instance('path.storage', $this->storagePath());

        $this->instance('url.assets', $this->assetsUrl());

        $this->instance('path.database', $this->databasePath());
    }

    public function assetsUrl() : string
    {
        return $this->isHmrMode() ? 'http://localhost:3000/resources/assets/' : '/assets/';
    }

    public function basePath($path = '') : string
    {
        return $this->basePath.($path ? DIRECTORY_SEPARATOR.$path : $path);
    }

    public function path($path = '')
    {
        return $this->basePath.DIRECTORY_SEPARATOR.'app'.($path ? DIRECTORY_SEPARATOR.$path : $path);
    }

    public function configPath($path = '')
    {
        return $this->basePath('config').($path ? DIRECTORY_SEPARATOR.$path : $path);
    }

    public function resourcePath($path = '')
    {
        return $this->basePath('resources').($path ? DIRECTORY_SEPARATOR.$path : $path);
    }

    public function bootstrapPath($path = '')
    {
        return $this->basePath('bootstrap').($path ? DIRECTORY_SEPARATOR.$path : $path);
    }

    public function publicPath($path = '')
    {
        return $this->basePath('public_html').($path ? DIRECTORY_SEPARATOR.$path : $path);
    }

    public function storagePath($path = '')
    {
        return $this->basePath('storage').($path ? DIRECTORY_SEPARATOR.$path : $path);
    }

    public function databasePath($path = '')
    {
        return $this->basePath('database').($path ? DIRECTORY_SEPARATOR.$path : $path);
    }

    public function addJsVariable(string $name, $data) : void
    {
        if (isset($this->jsVariables[$name]) && is_array($this->jsVariables[$name]) && is_array($data)) {
            $this->jsVariables[$name] = array_merge($this->jsVariables[$name], $data);
        } else {
            $this->jsVariables[$name] = $data;
        }
    }

    public function getJsVariables() : array
    {
        return $this->jsVariables;
    }

    public function getNamespace(): string
    {
        return 'App\\';
    }

    public function isDownForMaintenance(): bool
    {
        return false;
    }

    public function runningInConsole()
    {
        return php_sapi_name() == 'cli' || php_sapi_name() == 'phpdbg';
    }

    public function isWordPressAdmin()
    {
        if (isset($GLOBALS['current_screen']) && is_a($GLOBALS['current_screen'], 'WP_Screen')) {
            return $GLOBALS['current_screen']->in_admin();
        } elseif (defined('WP_ADMIN')) {
            return WP_ADMIN;
        }

        return false;
    }

    public function initRouter()
    {
        $router = new Router($this->events, $this);

        $this->instance('router', $router);

        $this->router->prefix('api')->group(function () use ($router) {
            require_once $this->basePath('routes/api.php');
        });

        $this->router->prefix('wp-admin')->group(function () use ($router) {
            require_once $this->basePath('routes/admin.php');
        });

        $this->router->group([], $this->basePath('routes/web.php'));

        $this->singleton('url', function () {
            return new UrlGenerator($this->router->getRoutes(), $this->request);
        });
    }

    public function boot()
    {
        $request = Request::capture();
        $this->instance('request', $request);

        $eventDispatcher = new Dispatcher($this);
        $this->bind('events', function ($app) use ($eventDispatcher) {
            return $eventDispatcher;
        });

        $this->initRouter();

        $this->bootProviders();

        $this->booted = true;
    }

    public function prepare()
    {
        Filter::add('rest_authentication_errors', function ($result) {
            if (true === $result || is_wp_error($result)) {
                return $result;
            }

            if (! is_user_logged_in()) {
                return new WP_Error(
                    'rest_not_logged_in',
                    __('You are not currently logged in.'),
                    ['status' => 401]
                );
            }

            return $result;
        });

        Action::add('init', function () {
            register_post_type('product', [
                'labels' => [
                    'name' => __('Product', 'test'),
                    'singular_name' => __('Product', 'test'),
                ],
                'public' => true,
                'has_archive' => true,
                'show_ui' => true,
                'show_in_menu' => true,
                'show_in_rest' => false,
                'supports' => ['title'],
                'rewrite' => ['slug' => 'product'],
            ]);
        }, 1);
    }

    public function version()
    {
        return static::VERSION;
    }

    public function run()
    {
        if (! $this->booted) {
            $this->boot();
        }

        $this->router->dispatch($this->request)->send();
    }

    public function booted()
    {
        return $this->booted;
    }

    public function environment(...$environments)
    {
        $env = config('app.env');

        if (count($environments) > 0) {
            $patterns = is_array($environments[0]) ? $environments[0] : $environments;

            return Str::is($patterns, $env);
        }

        return $env;
    }
}
