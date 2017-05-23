<?php

/**
 * Route URI's
 */
Route::group(['prefix' => config('youtube.routes.prefix')], function () {
    /**
     * Authentication
     */
    Route::get(config('youtube.routes.authentication_uri'), function () {
        if (!Youtube::getLatestAccessTokenFromDB()) {
            return response()->json(['url' => Youtube::createAuthUrl()], 200);
        }
        //dd(Youtube::createAuthUrl());
        //return redirect()->to(Youtube::createAuthUrl());
        return response()->json(['message' => 'access_token present in DB'], 200);
    });
    /**
     * Redirect
     */
    Route::get(config('youtube.routes.redirect_uri'), function (Illuminate\Http\Request $request) {
        $code = $request->get('code');
        if (is_null($code)) {
            throw new Exception('$_GET[\'code\'] is not set.');
        } else {
            try {
                $accessToken = Youtube::authenticate($_GET['code']);
                Youtube::saveAccessTokenToDB(json_encode($accessToken));
                $redirectTo = !empty(config('youtube.routes.redirect_uri')) ? url(config('youtube.routes.redirect_uri')) : route('/');
                return redirect()->secure($redirectTo);
            } catch (Exception $e) {
                // Do something here
                dd($e);
            }

        }
    });
});

//if (App::environment() != 'production')
//{
//    Route::get('youtube-upload-example/get-access-token', function() {
//        $authUrl = Youtube::createAuthUrl();
//        return view('laravel-youtube.example')->with(compact('authUrl'));
//    });
//
//    Route::get('youtube-upload-example/oauth2-callback', function() {
//
//        if (!isset($_GET['code']))
//        {
//            return Redirect::to('youtube-upload-example/get-access-token')->with('message', '$_GET[code] not set');
//        }
//        try {
//            $accessToken = Youtube::authenticate($_GET['code']);
//            Youtube::saveAccessTokenToDB(json_encode($accessToken));
//        } catch (Exception $e) {
//            // Do something here
//            dd($e);
//        }
//
//        return view('laravel-youtube.example')->with(compact('accessToken'));
//    });
//
//    Route::get('youtube-upload-example', function() {
//        if (!Youtube::getLatestAccessTokenFromDB())
//        {
//            return Redirect::to('youtube-upload-example/get-access-token')->with('message', 'Need to get an access token first');
//        }
//        return view('laravel-youtube.example');
//    });
//
//    Route::get('youtube-upload-example/get-uploads/{maxResults?}', function($maxResults = 50) {
//        if (!Youtube::getLatestAccessTokenFromDB())
//        {
//            return Redirect::to('youtube-upload-example/get-access-token')->with('message', 'Need to get an access token first');
//        }
//        return Response::json(Youtube::getUploads($maxResults));
//    });
//
//    Route::post('youtube-upload-example', function() {
//        $rules = array(
//            'title' => 'required',
//            'status' => 'required|in:public,private,unlisted',
//            'video' => 'required'
//        );
//        $validator = Validator::make(request()->all(), $rules);
//        if ($validator->fails())
//        {
//            return Redirect::to('youtube-upload-example')->withInput()->withErrors($validator);
//        }
//        try {
//            $youtubeVideoId = Youtube::upload(request()->all());
//            Session::put('youtubeVideoId', $youtubeVideoId);
//            return Redirect::to('youtube-upload-example')->with('message', 'Video uploaded successfully, it\'s probably still processing, so keep refreshing');
//        } catch (Exception $e) {
//            return Redirect::to('youtube-upload-example')->with('message', $e->getMessage());
//        }
//    });
//}