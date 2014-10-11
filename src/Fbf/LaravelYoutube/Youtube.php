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
		$this->client->setApprovalPrompt(\Config::get('laravel-youtube::approval_prompt'));
		$this->client->setRedirectUri(\URL::to(\Config::get('laravel-youtube::redirect_uri')));
		$this->client->setClassConfig('Google_Http_Request', 'disable_gzip', true);		
		$this->youtube = new \Google_Service_YouTube($this->client);
		$accessToken = $this->getLatestAccessTokenFromDB();
		if ($accessToken)
		{
			$this->client->setAccessToken($accessToken);
		}
	}

	/**
	 * Saves the access token to the database.
	 * @param $accessToken
	 */
	public function saveAccessTokenToDB($accessToken)
	{
		$data = array(
			'access_token' => $accessToken,
			'created_at' => \Carbon\Carbon::now(),
		);

		if(\Config::get('laravel-youtube::auth') == true) {
			$data['user_id'] = \Auth::user()->id;
		}

		\DB::table(\Config::get('laravel-youtube::table_name'))->insert($data);
	}

	/**
	 * Returns the last saved access token, if there is one, or null
	 * @return mixed
	 */
	public function getLatestAccessTokenFromDB()
	{
		$latest = \DB::table(\Config::get('laravel-youtube::table_name'))
				->orderBy('created_at', 'desc')
				->first();

		if(\Config::get('laravel-youtube::auth') == true){
			$latest = \DB::table(\Config::get('laravel-youtube::table_name'))
				->where('user_id', \Auth::user()->id)
				->orderBy('created_at', 'desc')->first();
		}
		
		if ($latest)
		{
			return $latest->access_token;
		}
		return null;
	}

	/*
	 * Return JSON response of uploaded videos 
	 * @return json
	 */
	public function getUploads($maxResults=50)
	{
		$channelsResponse = $this->youtube->channels->listChannels('contentDetails', array(
			'mine' => 'true',
		));

		foreach ($channelsResponse['items'] as $channel)
		{
			$uploadsListId = $channel['contentDetails']['relatedPlaylists']['uploads'];

			$playlistItemsResponse = $this->youtube->playlistItems->listPlaylistItems('snippet', array(
																									'playlistId' => $uploadsListId,
																									'maxResults' => $maxResults
																								));

			$items = [];
			foreach ($playlistItemsResponse['items'] as $playlistItem) 
			{
				$video = [];
				$video['videoId'] 		= $playlistItem['snippet']['resourceId']['videoId'];
				$video['title'] 		= $playlistItem['snippet']['title'];
				$video['publishedAt'] 	= $playlistItem['snippet']['publishedAt'];

				array_push($items, $video);
			}
		}

		return $items;
	}

	/**
	 * Uploads the passed video to the YouTube account identified by the access token in the DB and returns the
	 * uploaded video's YouTube Video ID. Attempts to automatically refresh the token if it's expired.
	 *
	 * @param array $data As is returned from \Input::all() given a form as per the one in views/example.blade.php
	 * @return string The ID of the uploaded video
	 * @throws \Exception
	 */
	public function upload(array $data)
	{
		$accessToken = $this->client->getAccessToken();

		if (is_null($accessToken))
		{
			throw new \Exception('You need an access token to upload');
		}

		// Attempt to refresh the access token if it's expired and save the new one in the database
		if ($this->client->isAccessTokenExpired())
		{
			$accessToken = json_decode($accessToken);
			$refreshToken = $accessToken->refresh_token;
			$this->client->refreshToken($refreshToken);
			$newAccessToken = $this->client->getAccessToken();
			$this->saveAccessTokenToDB($newAccessToken);
		}

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
	 * Deletes a video from the account specified by the Access Token
	 * Attempts to automatically refresh the token if it's expired.
	 *
	 * @param $id of the video to delete
	 * @return true if the video was deleted and false if it was not
	 * @throws \Exception
	 */
	public function delete($video_id)
	{
		$accessToken = $this->client->getAccessToken();

		if (is_null($accessToken))
		{
			throw new \Exception('You need an access token to delete.');
		}

		// Attempt to refresh the access token if it's expired and save the new one in the database
		if ($this->client->isAccessTokenExpired())
		{
			$accessToken = json_decode($accessToken);
			$refreshToken = $accessToken->refresh_token;
			$this->client->refreshToken($refreshToken);
			$newAccessToken = $this->client->getAccessToken();
			$this->saveAccessTokenToDB($newAccessToken);
		}

		$result = $this->youtube->videos->delete($video_id);

		if (!$result)
		{
			throw new \Exception("Couldn't delete the video from the youtube account.");
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