@section('head')
    <!-- TEST Section -->
@endsection

{{ Form::open('user/login', 'POST') }}

<p>{{ Form::label('email', 'EMail') }}
{{ Form::text('email') }}
{{ implode(', ', $errors->get('email')) }}</p>

<p>{{ Form::label('password', 'Password') }}
{{ Form::password('password') }}
{{ implode(', ', $errors->get('password')) }}</p>

<p>{{ Form::token() }}
{{ Form::submit('Log In') }}</p>

{{ Form::close() }}