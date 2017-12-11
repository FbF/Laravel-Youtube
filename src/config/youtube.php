<?php

return [

    /**
     * Application name
     */
    'application_name' => 'My App',

    /**
     * Client ID
     */
    'client_id' => '',

    /**
     * Client Secret
     */
    'client_secret' => '',

    /**
     * Scopes
     */
    'scopes' => [
        'https://www.googleapis.com/auth/youtube',
        'https://www.googleapis.com/auth/youtube.upload',
        'https://www.googleapis.com/auth/youtube.readonly'
    ],

    /**
     * Access type
     */
    'access_type' => 'offline',

    /**
     * Approval prompt
     */
    'approval_prompt' => 'auto',

    /**
     * Table name for Accesstokens
     */
    'table_name' => 'fbf_youtube_access_token',

    /**
     * Save and access the authentication tokens based on the Authenticated user.
     * Preferable when your system makes use of multiple users with Laravels authentication
     */
    'auth' => true,

    /**
     * Route URI's
     */
    'routes' => [
        /**
         * The prefix for the below URI's
         */
        'prefix' => 'youtube',
        /**
         * Redirect URI. This should be just the path part of the URI, excluding the http://mydomain.com/ part
         */
        'redirect_uri' => 'callback',
        /**
         * The authentication URI
         */
        'authentication_uri' => 'auth',
        /**
         * After we do authentication on Google side and we come back, we need to redirect user somewhere
         */
        'internal_redirect_url' => ''
    ],

    'playlist' => [
        //todo: add explanations
        'title' => 'Playlist title',
        'description' => 'Playlist description',
        'privacy' => 'unlisted',
        'kind' => 'youtube#'
    ],

    'playlist_item' => [
        'title' => 'Playlist item title',
        'kind' => 'youtube#video'
    ]

];