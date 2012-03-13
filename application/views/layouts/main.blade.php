<!doctype html>
<html>
    <head>
        <meta charset="utf-8">
        <title>GTD-Pad</title>
        @yield('head')
    </head>
    <body>
        <p>
            <a href="/gtd-pad/public">Home</a> | <a href="/gtd-pad/public/api-test.html">Test</a> | <a href="/gtd-pad/public/user/logout">Log Out</a> 
        </p>

        {{ $content }}
    </body>
</html