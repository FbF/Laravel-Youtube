<?php namespace Fbf\LaravelYoutube\Facades;

use Fbf\LaravelYoutube\Contracts\Youtube as YoutubeContract;
use Illuminate\Support\Facades\Facade;

class Youtube extends Facade {
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    //protected static function getFacadeAccessor() { return 'youtube'; }
    protected static function getFacadeAccessor()
    {
        return YoutubeContract::class;
    }
}