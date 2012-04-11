@layout('master')

@section('content')

{{ Form::open('user/login', 'POST') }}

<p>{{ Form::label('email', 'EMail') }}
{{ Form::text('email') }}
{{ implode(', ', $errors->get('email')) }}</p>

<p>{{ Form::label('password', 'Password') }}
{{ Form::password('password') }}
{{ implode(', ', $errors->get('password')) }}</p>

<p>{{ Form::label('remember', 'Remember Me') }}
{{ Form::checkbox('remember') }}
{{ implode(', ', $errors->get('remember')) }}</p>

<p>{{ Form::token() }}
{{ Form::submit('Log In') }}</p>

{{ Form::close() }}

@endsection