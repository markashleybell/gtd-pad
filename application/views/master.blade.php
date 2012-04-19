<!doctype html>
<html>
    <head>
        <meta charset="utf-8">
        <title>GTD-Pad</title>
        <link rel="stylesheet" type="text/css" href="/gtd-pad/public/css/desktop.css" />
        @yield('head')
    </head>
    <body>

        <div id="header">

            <p>
                <a href="/gtd-pad/public">Home</a> | <a href="/gtd-pad/public/api-test.html">Test</a> | <a href="/gtd-pad/public/user/signup">Sign Up</a> | <a href="/gtd-pad/public/user/logout">Log Out</a> 
            </p>

        </div>

        <div id="container">

            @yield('content')

        </div>

        <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
        <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.18/jquery-ui.min.js"></script>
        <script type="text/javascript" src="/gtd-pad/public/js/mustache.js"></script>

        @yield('foot')
    </body>
</html>