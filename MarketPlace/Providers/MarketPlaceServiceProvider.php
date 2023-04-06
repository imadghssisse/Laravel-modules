<?php

namespace Modules\MarketPlace\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;
use Modules\MarketPlace\Observers\ProductObserver;
use Modules\MarketPlace\Entities\Data;
use Laravel\Nova\Nova;

class MarketPlaceServiceProvider extends ServiceProvider
{
    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerTranslations();
        $this->registerConfig();
        // $this->registerViews();
        // $this->registerFactories();
        // $this->loadMigrationsFrom(module_path('MarketPlace', 'Database/Migrations'));
        Nova::serving(function () {
            Data::observe(ProductObserver::class);
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            module_path('MarketPlace', 'Config/config.php') => config_path('marketplace.php'),
        ], 'config');
        $this->mergeConfigFrom(
            module_path('MarketPlace', 'Config/config.php'), 'marketplace'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/marketplace');

        $sourcePath = module_path('MarketPlace', 'Resources/views');

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/marketplace';
        }, \Config::get('view.paths')), [$sourcePath]), 'marketplace');
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/marketplace');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'marketplace');
        } else {
            $this->loadTranslationsFrom(module_path('MarketPlace', 'Resources/lang'), 'marketplace');
        }
    }

    /**
     * Register an additional directory of factories.
     *
     * @return void
     */
    public function registerFactories()
    {
        if (! app()->environment('production') && $this->app->runningInConsole()) {
            app(Factory::class)->load(module_path('MarketPlace', 'Database/factories'));
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
