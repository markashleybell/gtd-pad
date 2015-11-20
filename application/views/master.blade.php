<!doctype html>
<html>
    <head>
        <meta charset="utf-8">
        <title>GTD-Pad</title>
        <link rel="stylesheet" type="text/css" href="/css/desktop.css" />
        @_yield('head')
    </head>
    <body>

        <div id="header">

            <p>
                <a href="/">Home</a> | <a href="/api-test.html">Test</a> | <a href="/user/signup">Sign Up</a> | <a href="/user/logout">Log Out</a> 
            </p>

        </div>

        <div id="container">

            @_yield('content')

        </div>

        <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
        <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.18/jquery-ui.min.js"></script>
        <script type="text/javascript" src="/js/mustache.js"></script>

        @_yield('foot')
    </body>
</html>