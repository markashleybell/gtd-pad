@section('head')
    


@endsection

<p id="load-message">Loading data</p>

@section('foot')

    <script type="text/javascript">

        var _config = { 
            pageId: 0,
            baseUrl: '/gtd-pad/public', // TODO: Read this from Laravel config
            templates: { // Container to hold template HTML for reuse
                page: null,
                item: null,
                list: null,
                listitem: null
            },
            // Check if all of the template HTML is currently loaded
            // If it is, fire off a callback
            templatesLoaded: function(callback) { 
                if(this.templates.page !== null 
                && this.templates.item !== null 
                && this.templates.list !== null 
                && this.templates.listitem !== null)
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

        // Initialise the page
        function init()
        {
            var container = $('#container');

            $.ajax({
                url: _config.baseUrl + '/api/v1/pages/' + _config.pageId,
                data: {  },
                dataType: 'json',
                type: 'GET',
                success: function(page, status, request) {

                    container.html(Mustache.render(_config.templates.page, page));

                    $.ajax({
                        url: _config.baseUrl + '/api/v1/pages/' + _config.pageId + '/items',
                        data: {  },
                        dataType: 'json',
                        type: 'GET',
                        success: function(items, status, request) {

                            $.each(items, function(i, item){

                                container.append(Mustache.render(_config.templates.item, item));
                            
                                if(item.list == 1)
                                {
                                    $('#item-' + item.id).append(Mustache.render(_config.templates.list, item));

                                    $.ajax({
                                        url: _config.baseUrl + '/api/v1/pages/' + _config.pageId + '/items/' + item.id + '/items',
                                        data: {  },
                                        dataType: 'json',
                                        type: 'GET',
                                        success: function(listitems, status, request) {
                                            
                                            var listContainer = $('#list-' + item.id);

                                            $.each(listitems, function(i, listitem){

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
        }

        $(function(){

            // Set global page ID variable from PHP value
            _config.pageId = {{$pageid}};

            // Load templates and fire init function when done
            loadTemplates(['page', 'item', 'list', 'listitem'], init);

        });

    </script>

@endsection