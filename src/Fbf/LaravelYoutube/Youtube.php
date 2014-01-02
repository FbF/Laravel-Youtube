<?php namespace Fbf\LaravelYoutube;

class Youtube {

	/**
	 * The injected instance of \Google_Client
	 * @var \Google_Client
	 */
	protected $client;

	/**
	 * The instance of \Google_Service_YouTube instantiated in the constructor
	 * @var \Google_Service_YouTube
	 */
	protected $youtube;

	/**
	 * Constructor stores the passed Google Client object, sets a bunch of config options from the config file, and also
	 * creates and instance of the \Google_Service_YouTube class and stores this for later use.
	 *
	 * @param \Google_Client $client
	 */
	public function __construct(\Google_Client $client)
	{
		$this->client = $client;
		$this->client->setApplicationName(\Config::get('laravel-youtube::application_name'));
		$this->client->setClientId(\Config::get('laravel-youtube::client_id'));
		$this->client->setClientSecret(\Config::get('laravel-youtube::client_secret'));
		$this->client->setScopes(\Config::get('laravel-youtube::scopes'));
		$this->client->setAccessType(\Config::get('laravel-youtube::access_type'));
		$this->client->setRedirectUri(\URL::to(\Config::get('laravel-youtube::redirect_uri')));
		$this->youtube = new \Google_Service_YouTube($this->client);
		$accessToken = \Config::get('laravel-youtube::access_token');
		if ($accessToken)
		{
			$this->client->setAccessToken($accessToken);
		}
	}

	/**
	 * Uploads the passed video to the YouTube account identified by the access token in the config file and returns the
	 * uploaded video's YouTube Video ID.
	 *
	 * @param array $data As is returned from \Input::all() given a form as per the one in views/example.blade.php
	 * @return string The ID of the uploaded video
	 * @throws \Exception
	 */
	public function upload(array $data)
	{
		$snippet = new \Google_Service_YouTube_VideoSnippet();
		if (array_key_exists('title', $data))
		{
			$snippet->setTitle($data['title']);
		}
		if (array_key_exists('description', $data))
		{
			$snippet->setDescription($data['description']);
		}
		if (array_key_exists('tags', $data))
		{
			$snippet->setTags($data['tags']);
		}
		if (array_key_exists('category_id', $data))
		{
			$snippet->setCategoryId($data['category_id']);
		}

		$status = new \Google_Service_YouTube_VideoStatus();
		if (array_key_exists('status', $data))
		{
			$status->privacyStatus = $data['status'];
		}

		$video = new \Google_Service_YouTube_Video();
		$video->setSnippet($snippet);
		$video->setStatus($status);

		$result = $this->youtube->videos->insert(
			'status,snippet',
			$video,
			array(
				'data'       => file_get_contents( $data['video']->getRealPath() ),
				'mimeType'   => $data['video']->getMimeType(),
				'uploadType' => 'multipart'
			)
		);

		if (!($result instanceof \Google_Service_YouTube_Video))
		{
			throw new \Exception('Expecting instance of Google_Service_YouTube_Video, got:' . $result);
		}

		return $result->getId();

	}

	/**
	 * Method calls are passed on to the injected instance of \Google_Client. Used for calls like:
	 *
	 *      $authUrl = Youtube::createAuthUrl();
	 *      $accessToken = Youtube::authenticate($_GET['code']);
	 *
	 * @param $method
	 * @param $args
	 * @return mixed
	 */
	public function __call($method, $args)
	{
		return call_user_func_array(array($this->client, $method), $args);
	}

}