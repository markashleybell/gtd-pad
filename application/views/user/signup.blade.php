@layout('master')

@section('content')

{{ Form::open('user/signup', 'POST') }}

<p>{{ Form::label('email', 'EMail') }}
{{ Form::text('email') }}
{{ implode(', ', $errors->get('email')) }}</p>

<p>{{ Form::label('password', 'Password') }}
{{ Form::password('password') }}
{{ implode(', ', $errors->get('password')) }}</p>

<p>{{ Form::label('password_confirmation', 'Confirm Password') }}
{{ Form::password('password_confirmation') }}
{{ implode(', ', $errors->get('password_confirmation')) }}</p>

<p>{{ Form::token() }}
{{ Form::submit('Sign Up') }}</p>

{{ Form::close() }}

@endsection