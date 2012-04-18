@layout('master')

@section('content')

<div id="navigation">

    <p><a href="#" id="add-page">Add new page</a></p>

    <ul id="page-navigation">
        
    </ul>

</div>

<div id="content">

    <div id="add-bar">
        <ul>
            <li><a href="#" id="add-list">Add List</a></li>
            <li><a href="#" id="add-note">Add Note</a></li>
        </ul>
    </div>

    <p id="load-message">Loading data</p>

</div>

@endsection

@section('foot')

    <script type="text/javascript">

        // Set global page ID variable from PHP value
        _pageId = {{$pageid}};

    </script>
    <script type="text/javascript" src="/gtd-pad/public/js/main.js?v={{time()}}"></script>

@endsection