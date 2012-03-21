@section('head')
    


@endsection

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

@section('foot')

    <script type="text/javascript">

        var _config = { 
            pageId: 0,
            baseUrl: '/gtd-pad/public', // TODO: Read this from Laravel config
            templates: { // Container to hold template HTML for reuse
                page: null,
                item: null,
                list: null,
                listitem: null,
                pagenavitem: null
            },
            forms: { // Container to hold template HTML for reuse
                page: null,
                item: null,
                listitem: null
            },
            // Check if all of the template HTML is currently loaded
            // If it is, fire off a callback
            templatesLoaded: function(callback) { 
                if(this.templates.page !== null 
                && this.templates.item !== null 
                && this.templates.list !== null 
                && this.templates.listitem !== null
                && this.templates.pagenavitem !== null)
                    callback();
            },
            // Check if all of the template HTML is currently loaded
            // If it is, fire off a callback
            formsLoaded: function(callback) { 
                if(this.forms.page !== null 
                && this.forms.item !== null 
                && this.forms.listitem !== null)
                    callback();
            }
        };

        // Retrieve the specified template HTML and store it in a global 'cache'
        function loadTemplates(keys, callback) {

            for(var i=0;i<keys.length;i++){

                // Give this it's own scope, otherwise we'll only ever load the last key
                // because the callbacks will fire after the loop has finished
                (function(key){
                    // Retrieve the template file and put the HTML content into the global var,
                    // then pass both data and template to the rendering function
                    $.get(_config.baseUrl + '/template/' + key + '.html', function (template) {
                        _config.templates[key] = template;
                        // Call a method of the template cache object which checks if all
                        // template HTML is loaded, with a callback to fire if it is
                        _config.templatesLoaded(callback);
                    });

                })(keys[i]);
            }
        }

        // Retrieve the specified template HTML and store it in a global 'cache'
        function loadForms(keys, callback) {

            for(var i=0;i<keys.length;i++){

                // Give this it's own scope, otherwise we'll only ever load the last key
                // because the callbacks will fire after the loop has finished
                (function(key){
                    // Retrieve the template file and put the HTML content into the global var,
                    // then pass both data and template to the rendering function
                    $.get(_config.baseUrl + '/template/' + key + '.form.html', function (template) {
                        _config.forms[key] = template;
                        // Call a method of the template cache object which checks if all
                        // template HTML is loaded, with a callback to fire if it is
                        _config.formsLoaded(callback);
                    });

                })(keys[i]);
            }
        }

        // Initialise the page
        function init()
        {
            // Set the container up for reuse
            var container = $('#content');

            // Retrieve page details 
            $.ajax({
                url: _config.baseUrl + '/api/v1/pages/' + _config.pageId,
                data: {  },
                dataType: 'json',
                type: 'GET',
                success: function(page, status, request) {

                    // Render the page template, passing the JSON model we retrieved in as a view model
                    container.find('#load-message').replaceWith(Mustache.render(_config.templates.page, page));

                    // Retrieve items for this page
                    $.ajax({
                        url: _config.baseUrl + '/api/v1/pages/' + _config.pageId + '/items',
                        data: {  },
                        dataType: 'json',
                        type: 'GET',
                        success: function(items, status, request) {

                            // Loop through the list of retrieved items for this page
                            $.each(items, function(i, item){

                                // Render an item template for each item, passing its JSON model in as a view model
                                container.append(Mustache.render(_config.templates.item, item));
                            
                                // If the item is a list
                                if(item.list == 1)
                                {
                                    // Append a list to it and get a reference to the list
                                    $('#item-' + item.id).append(Mustache.render(_config.templates.list, item));
                                    var listContainer = $('#list-' + item.id);

                                    // Retrieve the list items for this list
                                    $.ajax({
                                        url: _config.baseUrl + '/api/v1/pages/' + _config.pageId + '/items/' + item.id + '/items',
                                        data: {  },
                                        dataType: 'json',
                                        type: 'GET',
                                        success: function(listitems, status, request) {
                                            
                                            // Loop through the items retrieved for this list
                                            $.each(listitems, function(i, listitem){

                                                // Render an item template for each item, passing its JSON model in as a view model
                                                // and then append the new item to the list
                                                listContainer.append(Mustache.render(_config.templates.listitem, listitem));
                                            
                                            });

                                        },
                                        error: function(request, status, error) { console.log(error); }
                                    });
                                }

                            });

                        },
                        error: function(request, status, error) { console.log(error); }
                    });

                },
                error: function(request, status, error) { console.log(error); }
            });

            var pageNav = $('#page-navigation');

            // Retrieve all pages for this user to build page nav
            $.ajax({
                url: _config.baseUrl + '/api/v1/pages',
                data: {  },
                dataType: 'json',
                type: 'GET',
                success: function(pages, status, request) {
                    
                    // Loop through the items retrieved for this list
                    $.each(pages, function(i, page){

                        // Render an item template for each item, passing its JSON model in as a view model
                        // and then append the new item to the list
                        pageNav.append(Mustache.render(_config.templates.pagenavitem, page));
                    
                    });

                },
                error: function(request, status, error) { console.log(error); }
            });

            $('#content').on('click', '.edit-link', function(event){

                event.preventDefault();
                console.log($(this).parent().parent().attr('id'));

            });
        }

        function formSetup()
        {
            console.log('Setup forms');
        }

        $(function(){

            // Set global page ID variable from PHP value
            _config.pageId = {{$pageid}};

            // Load templates and fire init function when done
            loadTemplates(['page', 'item', 'list', 'listitem', 'pagenavitem'], init);

            loadForms(['page', 'item', 'listitem'], formSetup);

        });

    </script>

@endsection