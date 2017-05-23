<?php namespace Fbf\LaravelYoutube\Contracts;

interface Youtube
{
    /**
     * Saves the access token to the database.
     *
     * @param  string $accessToken
     */
    public function saveAccessTokenToDB($accessToken);

    /**
     * Returns the last saved access token, if there is one, or null
     *
     * @return mixed
     */
    public function getLatestAccessTokenFromDB();

    /**
     * Return JSON response of uploaded videos
     * @param int $maxResults
     * @return array
     */
    public function getUploads($maxResults);

    /**
     * Upload the video to YouTube
     *
     * @param array $data As is returned from \Input::all() given a form as per the one in views/example.blade.php
     * @return string The ID of the uploaded video
     * @throws \Exception
     */
    public function upload(array $data);

    /**
     * Create playlist and insert video into playlist
     *
     * @param $data
     * @param $youtubeVideoId
     * @return mixed
     * @throws \Exception
     */
    public function createPlaylist($data, $youtubeVideoId);

    /**
     * Get all playlist of a user
     *
     * @return \Google_Service_YouTube_PlaylistListResponse
     */
    public function getPlayLists();

    /**
     * Search playlists by title of the playlist
     *
     * @return mixed
     */
    public function searchForPlayList();

    /**
     * Set a Custom Thumbnail for the Upload
     *
     * @param  string $imagePath
     *
     * @return self
     */
    public function withThumbnail($imagePath);

    /**
     * Return the Video ID
     *
     * @return string
     */
    public function getVideoId();

    /**
     * Return the URL for the Custom Thumbnail
     *
     * @return string
     */
    public function getThumbnailUrl();

    /**
     * Delete a YouTube video by it's ID.
     *
     * @param  int $id
     *
     * @return bool
     */
    public function delete($id);

    /**
     * Check if a YouTube video exists by it's ID.
     *
     * @param  int $id
     *
     * @return bool
     */
    public function exists($id);
}