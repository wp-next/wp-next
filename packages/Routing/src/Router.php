<?php

namespace WpNext\Routing;

use Illuminate\Routing\Route;
use Illuminate\Routing\Router as IlluminateRouter;
use Illuminate\Support\Str;
use WpNext\Support\Facades\Action;

class Router extends IlluminateRouter
{
    public static $verbs = ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'WP'];

    protected $adminRoutes = [];

    public function wp($name, $action)
    {
        return $this->addRoute('WP', $name, $action);
    }

    public function admin($name, $action, $icon = null, $postition = null)
    {
        $title = Str::replaceFirst('/', '', $name);
        $title = str_replace(['-', '_', '.'], ' ', $title);
        $title = Str::title($title);
        $slug = Str::slug($title);

        Action::add('admin_menu', fn () => $this->registerAdminPage($name, $title, $slug, $action, $icon, $postition));
    }

    public function registerAdminPage($name, $title, $slug, $action, $icon, $postition)
    {
        if (Str::contains($name, '||')) {
            [$postType, $page] = explode('||', $name);

            $title = Str::title($page);
            $title = str_replace(['-', '_', '.'], ' ', $title);
            $slug = Str::slug($title);

            $this->registerAdminSubMenu($postType, $title, $slug, $action, $postition);

            return;
        }

        $this->registerAdminMenu($title, $slug, $action, $icon, $postition);
    }

    public function registerAdminMenu($title, $slug, $action, $icon, $postition)
    {
        add_menu_page(
            $title,
            $title,
            'edit_pages',
            $slug,
            function () use ($action) {
                $controller = (new $action[0]);

                return $controller->{$action[1]}(request());
            },
            $icon,
            $postition
        );
    }

    public function registerAdminSubMenu($parent, $title, $slug, $action, $postition)
    {
        if (post_type_exists($parent)) {
            $parentSlug = $parent === 'post' ? 'edit.php' : 'edit.php?post_type='.$parent;
        } elseif ($parent === 'tools') {
            $parentSlug = 'tools.php';
        } elseif ($parent === 'options') {
            $parentSlug = 'options-general.php';
        } elseif ($parent === 'themes') {
            $parentSlug = 'themes.php';
        } elseif ($parent === 'users') {
            $parentSlug = 'users.php';
        } else {
            $parentSlug = $parent;
        }

        add_submenu_page(
            $parentSlug,
            $title,
            $title,
            'edit_pages',
            $slug,
            function () use ($action) {
                $controller = (new $action[0]);

                return $controller->{$action[1]}();
            },
            $postition
        );
    }

    public function getAdminRoutes()
    {
        return $this->adminRoutes;
    }

    public function getAdminRoute($page)
    {
        return $this->adminRoutes[$page] ?? null;
    }

    protected function findWordPressRoute($request)
    {
        $conditions = config('app.conditions');

        $wpRoutes = collect($this->getRoutes()->getRoutesByMethod()['WP'])->sortByDesc(function ($route) {
            return Str::contains($route->uri, '||');
        });

        $wpRoute = $wpRoutes->first(function ($route) use ($conditions) {
            $routeCondition = $route->uri;
            $param = null;

            if (Str::contains($routeCondition, '||')) {
                [$routeCondition, $param] = explode('||', $routeCondition);
            }

            if (empty($conditions[$routeCondition])) {
                return;
            }

            if (! $conditions[$routeCondition]($param)) {
                return;
            }

            return $route;
        });

        if (! empty($wpRoute)) {
            $route = $wpRoute->bind($request);
            $this->current = $route;
            $this->container->instance(Route::class, $route);

            return $route;
        }
    }

    protected function findRoute($request)
    {
        $app = $this->container->app;

        if ($request->method() !== 'GET' && $request->method() !== 'WP') {
            $whoops = new \Whoops\Run;
            $whoops->pushHandler(new \Whoops\Handler\JsonResponseHandler);
            $whoops->register();
        }

        if (! is_null($app) && method_exists($app, 'isWordPressAdmin') && $app->isWordPressAdmin()) {
            $this->current = $route = (new AdminRoute($request, $this))->get();
            $this->container->instance(Route::class, $route);

            return $route;
        }

        return $this->findWordPressRoute($request) ?? parent::findRoute($request);
    }
}
