@section('head')
    


@endsection

<p>Loading data</p>

@section('foot')

    <script type="text/javascript">

        var _templates = { };

        // Retrieve the template HTML and return rendered HTML
        function renderTemplate(key, model, container, callback) {

            // If the global var is empty
            if (_templates[key] === undefined) {

                // Retrieve the template file and put the HTML content into the global var,
                // then pass both data and template to the rendering function
                $.get('/gtd-pad/public/template/' + key + '.html', function (template) {

                    _templates[key] = template;
                    container.append(Mustache.render(template, model));
                    callback();

                });

            }
            else
            {
                container.append(Mustache.render(_templates[key], model));
                callback();
            }
        }

        $(function(){

            var pageId = {{$pageid}};
            var baseUrl = '/gtd-pad/public';
            var container = $('#container');

            $.ajax({
                url: baseUrl + '/api/v1/pages/' + pageId,
                data: {  },
                dataType: 'json',
                type: 'GET',
                success: function(page, status, request) {

                    renderTemplate('page', page, container, function() {

                        $.ajax({
                            url: baseUrl + '/api/v1/pages/' + pageId + '/items',
                            data: {  },
                            dataType: 'json',
                            type: 'GET',
                            success: function(items, status, request) {

                                $.each(items, function(i, item){

                                    renderTemplate('item', item, container, function() {
                                
                                        if(item.list == 1)
                                        {
                                            $.ajax({
                                                url: baseUrl + '/api/v1/pages/' + pageId + '/items/' + item.id + '/items',
                                                data: {  },
                                                dataType: 'json',
                                                type: 'GET',
                                                success: function(listitems, status, request) {
                                                    
                                                    renderTemplate('list', item, $('#item-' + item.id), function() {

                                                        var listContainer = $('#list-' + item.id);

                                                        $.each(listitems, function(i, listitem){

                                                            renderTemplate('listitem', listitem, listContainer, function(){});
                                                        
                                                        });

                                                    });

                                                },
                                                error: function(request, status, error) { 

                                                    console.log(error);

                                                }
                                            });
                                        }

                                    });

                                });

                            },
                            error: function(request, status, error) { 

                                console.log(error);

                            }
                        });

                    });

                },
                error: function(request, status, error) { 

                    console.log(error);

                }
            });

        });

    </script>

@endsection