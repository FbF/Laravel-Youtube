<?php namespace Fbf\LaravelYoutube;

class YoutubeFacade extends \Illuminate\Support\Facades\Facade {
	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'youtube'; }

}