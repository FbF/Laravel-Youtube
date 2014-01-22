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
	'scopes' => array('https://www.googleapis.com/auth/youtube.upload', 'https://www.googleapis.com/auth/youtube.readonly'),

	/**
	 * Access type
	 */
	'access_type' => 'offline',

	/**
	 * Approval prompt
	 */
	'approval_prompt' => 'auto',

);