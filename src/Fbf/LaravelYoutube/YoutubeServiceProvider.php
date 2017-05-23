<?php namespace Fbf\LaravelYoutube;

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class YoutubeServiceProvider extends ServiceProvider
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
//        if ($this->isLegacyLaravel() || $this->isOldLaravel()) {
//            $this->package('fbf/laravel-youtube', 'fbf/laravel-youtube');
//        }

        $this->publishes([
            __DIR__ . '/../../config/youtube.php' => config_path('youtube.php'),
        ], 'config');

        $this->publishes([
            __DIR__ . '/../../migrations/' => database_path('migrations')
        ], 'migrations');

        include __DIR__ . '/../../routes/web.php';

    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
//        $this->app['youtube'] = $this->app->share(function () {
//            return new Youtube(new \Google_Client);
//        });
        $this->app->singleton(Contracts\Youtube::class, function () {
            return new Youtube(new \Google_Client, new Request);
        });
        $this->app->singleton('youtube', Contracts\Youtube::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        //return array('youtube');
        return [
            Contracts\Youtube::class,
            'youtube',
        ];
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