@if (Session::has('message'))
	<div class="alert">
		{{ Session::get('message') }}
	</div>
@endif

@if (Session::has('youtubeVideoId'))
	Last upload...<br />
	<iframe width="420" height="315" src="//www.youtube.com/embed/{{ Session::get('youtubeVideoId') }}?rel=0" frameborder="0" allowfullscreen></iframe>
@endif

@if (isset($authUrl))
	<a href="{{ $authUrl }}">Connect me</a>
@elseif (isset($accessToken))
	The access token has been added to your database: <b>{{ $accessToken }}</b> now <a href="/youtube-upload-example">try an upload</a>
@else
	{{ Form::open(array('url' => 'youtube-upload-example', 'class' => 'form', 'files' => true)) }}

		<div class="form-group{{ $errors->has('title') ? ' has-error' : '' }}">

			{{ Form::label('title', 'title', array('class' => 'control-label')) }}

			{{ Form::text('title', Input::old('title'), array('class' => 'form-control', 'placeholder' => 'title')) }}

			@if ($errors->has('title'))
				<span class="help-block">{{ $errors->first('title') }}</span>
			@endif

		</div>

		<div class="form-group{{ $errors->has('description') ? ' has-error' : '' }}">

			{{ Form::label('description', 'description', array('class' => 'control-label')) }}

			{{ Form::textarea('description', Input::old('description'), array('class' => 'form-control', 'placeholder' => 'description')) }}

			@if ($errors->has('description'))
				<span class="help-block">{{ $errors->first('description') }}</span>
			@endif

		</div>

		<div class="form-group{{ $errors->has('status') ? ' has-error' : '' }}">

			{{ Form::label('status', 'status', array('class' => 'control-label')) }}

			{{ Form::select('status', array('unlisted' => 'Unlisted', 'public' => 'Public', 'private' => 'Private'), Input::old('status')); }}

			@if ($errors->has('status'))
				<span class="help-block">{{ $errors->first('status') }}</span>
			@endif

		</div>

		<div class="form-group{{ $errors->has('video') ? ' has-error' : '' }}">

			{{ Form::label('video', 'video', array('class' => 'control-label')) }}

			{{ Form::file('video', array('class' => 'form-control')) }}

			@if ($errors->has('video'))
				<span class="help-block">{{ $errors->first('video') }}</span>
			@endif

		</div>

		{{ Form::submit('Submit', array('class' => 'btn btn-default')) }}

	{{ Form::close() }}
@endif