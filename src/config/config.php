<?php

return array(

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
	 * Redirect URI. This should be just the path part of the URI, excluding the http://mydomain.com/ part
	 */
	'redirect_uri' => 'youtube-upload-example/oauth2-callback',

	/**
	 * Scopes
	 */
	'scopes' => array(	'https://www.googleapis.com/auth/youtube.upload',
				'https://www.googleapis.com/auth/youtube.readonly',
				'https://www.googleapis.com/auth/youtube'),

	/**
	 * Access type
	 */
	'access_type' => 'offline',

	/**
	 * Approval prompt
	 */
	'approval_prompt' => 'auto',

	/**
	 * Table name for Accestokens 
	 */
	'table_name' => 'fbf_youtube_access_token',

	/** 
	 * Save and access the authentication tokens based on the Authenticated user. 
	 * Preferable when your system makes use of multiple users with Laravels authentication
	 */
	'auth' => false,

);


