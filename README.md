Laravel-Youtube
===============

A Laravel package to upload videos to a YouTube channel and get a list of uploaded videos

It is intended for use in a website where users can upload a video file which is then uploaded to a single Youtube
account, probably owned by the website owner, or to an individuals YouTube account. The account can be public or
unlisted and this essentially allows you to use Youtube as a video transcoding, hosting, serving and playback service
provider.

In addition to the upload and lsit functionality you can use in your own app, the package also includes the functionality
to get and store an access token, so that users can upload their videos to your account without you having to authorise
them each time. Google's access tokens are short lived, they only last 1 hour, but by default your app will get offline
access which means as well as an access_token, you also get a refresh_token that can be used to renew the access token.
The package handles storing the access_token and refresh_token in a database table (migration included) and will also
handle automatically getting a new access_token, using the refresh_token, when an access_token expires.

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

Run the migration

    php artisan migrate --package=fbf/laravel-youtube

## Usage

After getting an access token (see section on Authentication below), to upload a video, simply do:

```php
try {
    $youtubeVideoId = Youtube::upload($data);
} catch (Exception $e) {
    // Do something here
}
```

where `$data` is in the format of `Input::all()` when submitting a form like the one in `src/views/example.blade.php`,
or as follows:

```php
Youtube::upload(array(
    'title' => 'My video',
    'description' => 'This is what My video is about',
    'status' => 'unlisted', // or 'private' or 'public'
    'video' => $video, // Instance of Symfony\Component\HttpFoundation\File\UploadedFile see http://laravel.com/docs/requests#files
));
```

See the example in `Route::post('youtube-upload-example', function() {...})` in the `src/routes.php` file.

## Authentication

The config file contains several settings. To get the values for these settings, you need to register your app with the
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

If you are going to allow all uploads to go into a single channel, before the next step, ensure that the Google Account
you sign in with already has a YouTube Channel. If you have created a new Google Account just for your project, you
don't get a YouTube channel automatically, this is an extra step. When you are on www.youtube.com and you go to try and
upload a video manually, it will prompt you to create a channel. This gives you another Google account confusingly.
Finally, visit "http://mydomain.com/youtube-upload-example" in your browser, you should be redirected to
"http://mydomain.com/youtube-upload-example/get-access-token". Click "Connect Me" and then approve the app. You should
then be redirected back to http://mydomain.com/youtube-upload-example/oauth2-callback" which should display your access
token and say that it has been added to the database. Now you should be able to upload a video, try the example.

If you are going to allow different users to upload to their own channels set the `laravel-youtube::auth` config setting
to `true` and then create some actions and views in your app that use the functionality from the sample routes and
example view provided, to allow your users to authenticate for you.

## Todo

Include nice wrappers for other functionality in the YouTube Service within the Google API PHP Client library