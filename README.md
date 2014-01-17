Laravel-Youtube
===============

A Laravel package to upload videos to a YouTube channel

It is intended for use in a website where users can upload a video file which is then uploaded to a single Youtube
account, probably owned by the website owner. The account can be public or unlisted and this essentially allows you to
use Youtube as a video transcoding, hosting, serving and playback service provider.

In addition to the upload functionality you can use in your own app, the package also includes the functionality to get
an access token that you can include in the package's config file, so that users can upload their videos to your account
without you having to authorise them each time.

Also included is sample code for a form and a simple route closure callback that validates the form and uploads the
video to Youtube.

All the sample routes do not get included when in production. They only work on local, staging etc. Your production code
should only ever call the methods in the Usage section below.

## Installation

Add the following to you composer.json file

    "fbf/laravel-youtube": "dev-master"

Run

    composer update

Add the following to the providers array in app/config/app.php

    'Fbf\LaravelYoutube\LaravelYoutubeServiceProvider'

Add the following to the aliases array in app/config/app.php

    'Youtube'         => 'Fbf\LaravelYoutube\YoutubeFacade',

Publish the config

    php artisan config:publish fbf/laravel-youtube

## Usage

After getting an access token (see section on Authentication below), to upload a video, simply do:

    $youtubeVideoId = Youtube::upload($data);

where `$data` is in the format of `Input::all()` when submitting a form like the one in `src/views/example.blade.php`.

See the example in `Route::post('youtube-upload-example', function() {...})` in the `src/routes.php` file.

## Authentication

The config file contains several settings, all except the access_token are required in order to get the access_token.
To get the values for these settings, you need to register your app with the
<a href="https://cloud.google.com/console">Google Developers Console</a>.

Create a project, give it a name and an ID, but to be honest, it doesn't really matter what these are as no one else
will ever see them.

In the APIs screen for your new project, ensure YouTube Data API v3 is on.

In the credentials screen, create a new client ID. Application type should be Web Application, Authorized Javascript
origins aren't used, so leave as is, Authorized redirect URI should be your redirect uri. The package includes a route
you can use as the redirect URI, which is "youtube-upload-example/oauth2-callback", so the value you should use here is
the absolute URL, including the domain, e.g. "http://mydomain.com/youtube-upload-example/oauth2-callback". A hostname
including localhost doesn't work, however you can still do all this on your local development machine, you just need to
alias a real domain in your VirtualHost config and add it to your hosts file.

Now copy the client ID and client secret into the `app/config/packages/fbf/laravel-youtube/config.php` file.

Finally, visit "http://mydomain.com/youtube-upload-example" in your browser, you should be redirected to
"http://mydomain.com/youtube-upload-example/get-access-token". Click "Connect Me" and then approve the app. You should
then be redirected back to http://mydomain.com/youtube-upload-example/oauth2-callback" which should display your access
token.

Ensure that the Google Account you sign in with already has a YouTube Channel. If you have created a new Google Account
just for your project, you don't get a YouTube channel automatically, this is an extra step.

Copy the full JSON string into the `'access_token'` key in the
`app/config/packages/fbf/laravel-youtube/config.php` file and then click the try upload button. Confusingly Google call
both the `'access_token'` field in the JSON object and the full JSON object, including the refresh_token, bearer,
expires fields etc, an Access Token. The client library deals with both of them interchangeably too. The difference is
that if you use the full JSON string, including the refresh token, the Google library should automatically get a new
access_token using the refresh_token, when this access_token expires, so your app should work for more than 1 hour!!!

To confirm, copy the full JSON string into the config file's key labelled `'access_token'`. E.g.
`'access_token' => '{"access_token":"ab12.1.ABCdE_FGhIjklMnOpQRsTuvWxYzAB1cdEFGH2aslfhjkljha_3KG£76g2HJGhg3ukKFSXGd","token_type":"Bearer","expires_in":3598,"created":1389173725}',`

The `access_token` string includes a `refresh_token` if the `'access_type'` is set to `'offline'` in the config file.
This means that the actual `access_token` will be automatically exchanged for a new one, of it has expired.

## Todo

Include nice wrappers for other functionality in the YouTube Service within the Google API PHP Client library