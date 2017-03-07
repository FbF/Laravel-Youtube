<?php namespace Fbf\LaravelYoutube;

use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class LaravelYoutubeServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->isLegacyLaravel() || $this->isOldLaravel()) {
            $this->package('fbf/laravel-youtube', 'fbf/laravel-youtube');
        }

        $this->loadMigrationsFrom(__DIR__.'/../../migrations');
        $this->publishes(array(__DIR__ . '/../../config/laravel-youtube.php' => config_path('laravel-youtube.php')));

        include __DIR__ . '/../../routes.php';

    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app['youtube'] = $this->app->share(function () {
            return new Youtube(new \Google_Client);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('youtube');
    }

    public function isLegacyLaravel()
    {
        return Str::startsWith(Application::VERSION, array('4.1.', '4.2.'));
    }
    public function isOldLaravel()
    {
        return Str::startsWith(Application::VERSION, '4.0.');
    }

}