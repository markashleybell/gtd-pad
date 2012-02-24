
<h1>Data Connectivity Test</h1>

<h2>Connecting to local MySQL database</h2>

<h3>Users</h3>

<p>

@foreach ($users as $user)
	{{$user->email}}<br />
@endforeach

</p>
