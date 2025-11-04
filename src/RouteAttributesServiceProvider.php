<?php

namespace Spatie\RouteAttributes;

use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;

class RouteAttributesServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/route-attributes.php' => config_path('route-attributes.php'),
            ], 'config');
        }

        $this->registerRoutes();
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/route-attributes.php', 'route-attributes');
    }

    protected function registerRoutes(): void
    {
        if (! $this->shouldRegisterRoutes()) {
            return;
        }

        $routeRegistrar = $this->app->make(RouteRegistrar::class, [app()->router])
            ->useMiddleware(config('route-attributes.middleware') ?? []);

        collect($this->getRouteDirectories())->each(function (array $config) use ($routeRegistrar) {
            $options = Arr::except($config, ['directory', 'namespace', 'base_path', 'patterns', 'not_patterns', 'use_pathname']);

            $routeRegistrar
                ->useRootNamespace($config['namespace'] ?? app()->getNamespace())
                ->useBasePath($config['base_path'] ?? (isset($config['namespace']) ? $config['directory'] : app()->path()))
                ->group($options, fn () => $routeRegistrar->registerDirectory(
                    directories: $config['directory'] ?? app()->path(),
                    patterns: $config['patterns'] ?? [],
                    notPatterns: $config['not_patterns'] ?? [],
                    usePathname: $config['use_pathname'] ?? false,
                ));
        });
    }

    private function shouldRegisterRoutes(): bool
    {
        if (! config('route-attributes.enabled')) {
            return false;
        }

        if ($this->app->routesAreCached()) {
            return false;
        }

        return true;
    }

    private function getRouteDirectories(): array
    {
        return config('route-attributes.directories');
    }
}
