<?php

Route::get('youtube-upload-example/get-access-token', function() {
	$authUrl = Youtube::createAuthUrl();
	return View::make('laravel-youtube::example')->with(compact('authUrl'));
});

Route::get('youtube-upload-example/oauth2-callback', function() {
	if (!isset($_GET['code']))
	{
		return Redirect::to('youtube-upload-example/get-access-token')->with('message', '$_GET[code] not set');
	}
	$accessToken = Youtube::authenticate($_GET['code']);
	return View::make('laravel-youtube::example')->with(compact('accessToken'));
});

Route::get('youtube-upload-example', function() {
	if (!Config::get('laravel-youtube::access_token'))
	{
		return Redirect::to('youtube-upload-example/get-access-token')->with('message', 'Need to get an access token first');
	}
	return View::make('laravel-youtube::example');
});

Route::post('youtube-upload-example', function() {
	$rules = array(
		'title' => 'required',
		'status' => 'required|in:public,private,unlisted',
		'video' => 'required'
	);
	$validator = Validator::make(Input::all(), $rules);
	if ($validator->fails())
	{
		return Redirect::to('youtube-upload-example')->withInput()->withErrors($validator);
	}
	try {
		$youtubeVideoId = Youtube::upload(Input::all());
		Session::put('youtubeVideoId', $youtubeVideoId);
		return Redirect::to('youtube-upload-example')->with('message', 'Video uploaded successfully, it\'s probably still processing, so keep refreshing');
	} catch (Exception $e) {
		return Redirect::to('youtube-upload-example')->with('message', $e->getMessage());
	}
});