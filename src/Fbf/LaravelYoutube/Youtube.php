<?php namespace Fbf\LaravelYoutube;

class Youtube
{

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
        $this->client->setApplicationName(config('youtube.application_name'));
        $this->client->setClientId(config('youtube.client_id'));
        $this->client->setClientSecret(config('youtube.client_secret'));
        $this->client->setScopes(config('youtube.scopes'));
        $this->client->setApprovalPrompt(config('youtube.approval_prompt'));
        $this->client->setAccessType(config('youtube.access_type')); //generates refresh token
        $this->client->setApprovalPrompt("force");
        $this->client->setRedirectUri(url(config('youtube.routes.prefix') . '/' . config('youtube.routes.redirect_uri')));
        //$this->client->setClassConfig('Google_Http_Request', 'disable_gzip', true);
        $this->youtube = new \Google_Service_YouTube($this->client);
        if ($accessToken = $this->getLatestAccessTokenFromDB()) {
            $this->client->setAccessToken($accessToken);
        }
    }

    /**
     * Saves the access token to the database.
     * @param $accessToken
     */
    public function saveAccessTokenToDB($accessToken)
    {

        //todo: check is there access_token field valid
        if (is_array($accessToken)) {
            if (!empty($accessToken['error'])) {
                return;
            }
            $accessToken = json_encode($accessToken);
        };
        //dd($accessToken);
        $data = array(
            'access_token' => $accessToken,
            'created_at' => \Carbon\Carbon::now(),
        );

        if (config('youtube.auth') == true) {
            $data['user_id'] = \Auth::user()->id;
        }
        //dd($data);
        \DB::table(config('youtube.table_name'))->insert($data);
    }

    /**
     * Returns the last saved access token, if there is one, or null
     * @return mixed
     */
    public function getLatestAccessTokenFromDB()
    {
        if (config('youtube.auth') == true) {
            $latest = \DB::table(config('youtube.table_name'))
                ->where('user_id', \Auth::user()->id)
                ->orderBy('created_at', 'desc')->first();
        } else {
            $latest = \DB::table(config('youtube.table_name'))
                ->orderBy('created_at', 'desc')
                ->first();
        }

        return $latest ? (is_array($latest) ? $latest['access_token'] : $latest->access_token) : null;
    }

    /**
     * Return JSON response of uploaded videos
     * @param int $maxResults
     * @return array
     */
    public function getUploads($maxResults = 50)
    {
        $channelsResponse = $this->youtube->channels->listChannels('contentDetails', array(
            'mine' => 'true',
        ));

        foreach ($channelsResponse['items'] as $channel) {
            $uploadsListId = $channel['contentDetails']['relatedPlaylists']['uploads'];

            $playlistItemsResponse = $this->youtube->playlistItems->listPlaylistItems('snippet', array(
                'playlistId' => $uploadsListId,
                'maxResults' => $maxResults
            ));

            $items = [];
            foreach ($playlistItemsResponse['items'] as $playlistItem) {
                $video = [];
                $video['videoId'] = $playlistItem['snippet']['resourceId']['videoId'];
                $video['title'] = $playlistItem['snippet']['title'];
                $video['publishedAt'] = $playlistItem['snippet']['publishedAt'];

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
        $this->handleAccessToken();

        /* ------------------------------------
        #. Setup the Snippet
        ------------------------------------ */
        $snippet = new \Google_Service_YouTube_VideoSnippet();
        if (array_key_exists('title', $data)) {
            $snippet->setTitle($data['title']);
        }
        if (array_key_exists('description', $data)) {
            $snippet->setDescription($data['description']);
        }
        if (array_key_exists('tags', $data)) {
            $snippet->setTags($data['tags']);
        }
        if (array_key_exists('category_id', $data)) {
            $snippet->setCategoryId($data['category_id']);
        }

        /* ------------------------------------
        #. Set the Privacy Status
        ------------------------------------ */
        $status = new \Google_Service_YouTube_VideoStatus();
        if (array_key_exists('status', $data)) {
            $status->privacyStatus = $data['status'];
        }

        /* ------------------------------------
        #. Set the Snippet & Status
        ------------------------------------ */
        $video = new \Google_Service_YouTube_Video();
        $video->setSnippet($snippet);
        $video->setStatus($status);

        /* ------------------------------------
        #. Set the Chunk Size
        ------------------------------------ */
        $chunkSize = 1 * 1024 * 1024;
        /* ------------------------------------
        #. Set the defer to true
        ------------------------------------ */
        $this->client->setDefer(true);
        /* ------------------------------------
        #. Build the request
        ------------------------------------ */
        $insert = $this->youtube->videos->insert(
            'status,snippet',
            $video
        );

        //        if (!($insert instanceof \Google_Service_YouTube_Video)) {
        //            throw new \Exception('Expecting instance of Google_Service_YouTube_Video, got:' . $result);
        //        }

        /* ------------------------------------
        #. Upload
        ------------------------------------ */
        $media = new \Google_Http_MediaFileUpload(
            $this->client,
            $insert,
            'video/*',
            null,
            true,
            $chunkSize
        );
        /* ------------------------------------
        #. Set the Filesize
        ------------------------------------ */
        $media->setFileSize(filesize($data['video']->getRealPath()));
        /* ------------------------------------
        #. Read the file and upload in chunks
        ------------------------------------ */
        $status = false;
        $handle = fopen($data['video']->getRealPath(), "rb");
        while (!$status && !feof($handle)) {
            $chunk = fread($handle, $chunkSize);
            $status = $media->nextChunk($chunk);
        }
        fclose($handle);
        $this->client->setDefer(false);
        /* ------------------------------------
        #. Set the Uploaded Video ID
        ------------------------------------ */
        $this->videoId = $status['id'];
        //return $this;

        //$this->createPlaylist($data);

        return $this->videoId;

    }

    /**
     * Create playlist and insert video into playlist
     *
     * @param $data
     * @param $youtubeVideoId
     * @return mixed
     * @throws \Exception
     */
    public function createPlaylist($data, $youtubeVideoId)
    {
        $this->handleAccessToken();
        try {
            $user = auth()->user();

            // This code creates a new, private playlist in the authorized user's
            // channel and adds a video to the playlist.
            // 1. Create the snippet for the playlist. Set its title and description.
            $playlistSnippet = new \Google_Service_YouTube_PlaylistSnippet();
            $playlistSnippet->setTitle('FNF Playlist for: ' . $user->username . '-' . $user->id);
            $playlistSnippet->setDescription("A private playlist created for {$user->username}");

            // 2. Define the playlist's status.
            $playlistStatus = new \Google_Service_YouTube_PlaylistStatus();
            $playlistStatus->setPrivacyStatus('unlisted');

            // 3. Define a playlist resource and associate the snippet and status
            // defined above with that resource.
            $youTubePlaylist = new \Google_Service_YouTube_Playlist();
            $youTubePlaylist->setSnippet($playlistSnippet);
            $youTubePlaylist->setStatus($playlistStatus);
            $youTubePlaylist->setKind('youtube#' . $user->username);


            // 4. Call the playlists.insert method to create the playlist. The API
            // response will contain information about the new playlist.
            //todo: check if there is already playlist for this user
            $playlist = $this->searchForPlayList();
            if (!empty($playlist)) {
                //dd($playlist);
                $playlistId = $playlist['id'];
            } else {
                $playlistResponse = $this->youtube->playlists->insert('snippet,status', $youTubePlaylist, []);
                $playlistId = $playlistResponse['id'];
            }

            // 5. Add a video to the playlist. First, define the resource being added
            // to the playlist by setting its video ID and kind.
            $resourceId = new \Google_Service_YouTube_ResourceId();
            $resourceId->setVideoId($youtubeVideoId);
            $resourceId->setKind('youtube#video');

            // Then define a snippet for the playlist item. Set the playlist item's
            // title if you want to display a different value than the title of the
            // video being added. Add the resource ID and the playlist ID retrieved
            // in step 4 to the snippet as well.
            $playlistItemSnippet = new \Google_Service_YouTube_PlaylistItemSnippet();
            $playlistItemSnippet->setTitle('First video in the test playlist');
            $playlistItemSnippet->setPlaylistId($playlistId);
            $playlistItemSnippet->setResourceId($resourceId);

            // Finally, create a playlistItem resource and add the snippet to the
            // resource, then call the playlistItems.insert method to add the playlist
            // item.
            $playlistItem = new \Google_Service_YouTube_PlaylistItem();
            $playlistItem->setSnippet($playlistItemSnippet);
            $playlistItemResponse = $this->youtube->playlistItems->insert(
                'snippet,contentDetails', $playlistItem, array());

            return $playlistId;
        } catch (Google_Service_Exception $e) {
            //            $htmlBody = sprintf('<p>A service error occurred: <code>%s</code></p>',
            //                                htmlspecialchars($e->getMessage()));

            throw new \Exception($e->getMessage());
        } catch (Google_Exception $e) {
            //$htmlBody = sprintf('<p>An client error occurred: <code>%s</code></p>',
            //                   htmlspecialchars($e->getMessage()));
            throw new \Exception($e->getMessage());
        }

    }

    /**
     * Get all playlist of a user
     *
     * @return \Google_Service_YouTube_PlaylistListResponse
     */
    public function getPlayLists()
    {
        return $this->youtube->playlists->listPlaylists('contentDetails, id, player, status, snippet', ['mine' => true]);
    }

    /**
     * Search playlists by title of the playlist
     *
     * @return mixed
     */
    public function searchForPlayList()
    {
        //dd($this->getPlayLists());
        $user = auth()->user();
        $playLists = $this->getPlayLists();

        return collect($playLists['modelData']['items'])->filter(function ($item) use ($user) {
            //FNF Playlist for: student
            //
            return $item['snippet']['title'] == 'FNF Playlist for: ' . $user->username . '-' . $user->id;
            //dd($item['snippet']['title']);
        })->first();
        //dd($s);
        //collect($playLists['modelData'])

    }

    /**
     * Set a Custom Thumbnail for the Upload
     *
     * @param  string $imagePath
     *
     * @return self
     */
    function withThumbnail($imagePath)
    {
        try {
            $videoId = $this->getVideoId();
            // Specify the size of each chunk of data, in bytes. Set a higher value for
            // reliable connection as fewer chunks lead to faster uploads. Set a lower
            // value for better recovery on less reliable connections.
            $chunkSizeBytes = 1 * 1024 * 1024;
            // Setting the defer flag to true tells the client to return a request which can be called
            // with ->execute(); instead of making the API call immediately.
            $this->client->setDefer(true);
            // Create a request for the API's thumbnails.set method to upload the image and associate
            // it with the appropriate video.
            $setRequest = $this->youtube->thumbnails->set($videoId);
            // Create a MediaFileUpload object for resumable uploads.
            $media = new \Google_Http_MediaFileUpload(
                $this->client,
                $setRequest,
                'image/png',
                null,
                true,
                $chunkSizeBytes
            );
            $media->setFileSize(filesize($imagePath));
            // Read the media file and upload it chunk by chunk.
            $status = false;
            $handle = fopen($imagePath, "rb");
            while (!$status && !feof($handle)) {
                $chunk = fread($handle, $chunkSizeBytes);
                $status = $media->nextChunk($chunk);
            }
            fclose($handle);
            // If you want to make other calls after the file upload, set setDefer back to false
            $this->client->setDefer(false);
            $this->thumbnailUrl = $status['items'][0]['default']['url'];
        } catch (\Google_Service_Exception $e) {
            die($e->getMessage());
        } catch (\Google_Exception $e) {
            die($e->getMessage());
        }
        return $this;
    }


    /**
     * Return the Video ID
     *
     * @return string
     */
    function getVideoId()
    {
        return $this->videoId;
    }

    /**
     * Return the URL for the Custom Thumbnail
     *
     * @return string
     */
    function getThumbnailUrl()
    {
        return $this->thumbnailUrl;

    }

    /**
     * Deletes a video from the account specified by the Access Token
     * Attempts to automatically refresh the token if it's expired.
     *
     * @param $id of the video to delete
     * @return true if the video was deleted and false if it was not
     * @throws \Exception
     */
    public function delete($id)
    {
        $this->handleAccessToken();

        if (!$this->exists($id))
            return false;

        $result = $this->youtube->videos->delete($id);

        if (!$result) {
            throw new \Exception("Couldn't delete the video from the youtube account.");
        }

        return $result->getId();

    }

    /**
     * Check if a YouTube video exists by it's ID.
     *
     * @param  int $id
     *
     * @return bool
     */
    public function exists($id)
    {
        $this->handleAccessToken();
        $response = $this->youtube->videos->listVideos('status', ['id' => $id]);
        if (empty($response->items))
            return false;
        return true;
    }

    /**
     * Handle the Access token.
     */
    private function handleAccessToken()
    {
        $accessToken = $this->client->getAccessToken();

        if (is_null($accessToken)) {
            throw new \Exception('An access token is required to delete a video.');
        }

        if ($this->client->isAccessTokenExpired()) {
            if (!is_array($accessToken)) {
                $accessToken = json_decode($accessToken);
                $refreshToken = $accessToken->refresh_token;
            } else {
                $refreshToken = $accessToken['refresh_token'];
            }
            $this->client->refreshToken($refreshToken);
            $newAccessToken = $this->client->getAccessToken();
            $this->saveAccessTokenToDB($newAccessToken);
        }
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