<?php

namespace Modules\Actionsboard\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;
use Illuminate\Support\Facades\Queue;
use Illuminate\Queue\Events\JobProcessed;
use Modules\Actionsboard\Http\Controllers\CurrentAction;
use App\Data;

class ActionsboardServiceProvider extends ServiceProvider
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
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        Queue::after(function (JobProcessed $event) {
          $payload = json_decode( $event->job->getRawBody() );
          if (strpos($payload->job, 'Modules\Actionsboard')) {
            $data = unserialize( $payload->data->command );
            $currentACtion = new CurrentAction();
            $action = Data::find($data->id);
            if ($payload->displayName === "Modules\Actionsboard\Jobs\SetMailForCurrentAction" && $data->user->id == $action->data['owner']) {
              $currentACtion->checkCurrentAction($data);
            } else if ($payload->displayName == "Modules\Actionsboard\Jobs\NotifieOwnerActions" && $data->user->id == $action->data['owner']) {
              $currentACtion->createSecondNotifOwner($data);
            }
          }
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
            __DIR__.'/../Config/config.php' => config_path('actionsboard.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'actionsboard'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/actionsboard');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/actionsboard';
        }, \Config::get('view.paths')), [$sourcePath]), 'actionsboard');
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/actionsboard');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'actionsboard');
        } else {
            $this->loadTranslationsFrom(__DIR__ .'/../Resources/lang', 'actionsboard');
        }
    }

    /**
     * Register an additional directory of factories.
     *
     * @return void
     */
    public function registerFactories()
    {
        if (! app()->environment('production')) {
            app(Factory::class)->load(__DIR__ . '/../Database/factories');
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
