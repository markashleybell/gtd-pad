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

        function addForm(type, location) {

            var model = {
                id: 0,
                title: '',
                body: ''
            };

            switch(type)
            {
                case 'list':
                    model.list = true;
                    location.prepend(Mustache.render(_config.templates.item, model));
                    $('#item-0').append(Mustache.render(_config.forms.item, model)).find('.content').hide();
                    $('#item-0').append(Mustache.render(_config.templates.list, model)).find('.add-listitem').hide();
                    break;
                case 'note':
                    model.list = false;
                    location.prepend(Mustache.render(_config.templates.item, model));
                    $('#item-0').append(Mustache.render(_config.forms.item, model)).find('.content').hide();
                    break;
                case 'listitem':
                    location.append(Mustache.render(_config.templates.listitem, model));
                    $('#listitem-0').append(Mustache.render(_config.forms.listitem, model)).find('.content').hide();
                    break;
            }
        }

        function addNewPage() {
            var model = {
                title: 'New Page',
                displayorder: -1
            };

            // Update display order
            $.ajax({
                url: _config.baseUrl + '/api/v1/pages',
                data: model,
                dataType: 'json',
                type: 'POST',
                success: function(page, status, request) {
                    
                    // Redirect to the new page
                    window.location.href = _config.baseUrl + '/p/' + page.id

                },
                error: function(request, status, error) { console.log(error); }
            });
        }

        function updatePageNavDisplayOrder(event, ui) {
            var model = {};
            $('#page-navigation li').each(function(i, item){
                model['displayorder-' + item.id.split('-')[1]] = i;
            });
            // Update display order
            $.ajax({
                url: _config.baseUrl + '/api/v1/pages/order',
                data: model,
                dataType: 'json',
                type: 'PUT',
                success: function(listitems, status, request) {
                    
                    // We don't need to do anything here...

                },
                error: function(request, status, error) { console.log(error); }
            });
        }

        function updateItemDisplayOrder(event, ui) {
            var model = {};
            $('.item-container').each(function(i, item){
                model['displayorder-' + item.id.split('-')[1]] = i;
            });
            // Update display order
            $.ajax({
                url: _config.baseUrl + '/api/v1/pages/' + _config.pageId + '/items/order',
                data: model,
                dataType: 'json',
                type: 'PUT',
                success: function(listitems, status, request) {
                    
                    // We don't need to do anything here...

                },
                error: function(request, status, error) { console.log(error); }
            });
        }

        function updateListItemDisplayOrder(event, ui) {
            
            $('.item-list').each(function(i, item){

                var listId = $(this).parent().attr('id').split('-')[1];

                var itemId = $(this).attr('id').split('-')[1];

                var itemIdList = [];

                $(this).find('.listitem-container').each(function(i, item){
                    itemIdList.push(item.id.split('-')[1]);
                });

                // Update display order
                $.ajax({
                    url: _config.baseUrl + '/api/v1/pages/' + _config.pageId + '/items/' + itemId + '/items/order',
                    data: { items: itemIdList.join(',') },
                    dataType: 'json',
                    type: 'PUT',
                    success: function(listitems, status, request) {
                        
                        // We don't need to do anything here...

                    },
                    error: function(request, status, error) { console.log(error); }
                });
            });
        }

        function deleteItem(type, delid) {

            var apiCall = '';

            switch(type)
            {
                case 'item':
                    apiCall = '/items/' + delid;
                    break;
                case 'listitem':
                    var parentId = $('#listitem-' + delid).parent().parent().attr('id').split('-')[1];
                    apiCall = '/items/' + parentId + '/items/' + delid;
                    break;
                case 'page':
                    break;
            }

            // Update display order
            $.ajax({
                url: _config.baseUrl + '/api/v1/pages/' + _config.pageId + apiCall,
                data: { id: delid },
                dataType: 'json',
                type: 'DELETE',
                success: function(result, status, request) {
                    
                    // If we've deleted a page, redirect to the home page
                    if(type === 'page')
                    {
                        window.location.href = _config.baseUrl;
                    }
                    else
                    {
                        // Remove the item
                        $('#' + type + '-' + delid).remove();
                    }

                },
                error: function(request, status, error) { console.log(error); }
            });
            
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
                    var itemContainer = container.find('#items')

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
                                itemContainer.append(Mustache.render(_config.templates.item, item));
                            
                                // If the item is a list
                                if(item.list)
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

                            $("#items").sortable({
                                // connectWith: ['.page-drop'],
                                stop: updateItemDisplayOrder,
                                handle: '.drag-link'
                            });

                            $(".item-list").sortable({
                                connectWith: ['.item-list'],
                                stop: updateListItemDisplayOrder,
                                handle: '.drag-link'
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

                    pageNav.sortable({
                        stop: updatePageNavDisplayOrder,
                        handle: '.drag-link'
                    });

                    // Allow dropping of items onto page navigation items (for
                    // moving items to a new page)
                    $('.page-drop').droppable({
                        accept: '.item-container',
                        tolerance: 'pointer',
                        hoverClass: 'drophover',
                        drop: function(event, ui) { 

                            var itemId = ui.draggable.attr('id').split('-')[1];
                            var newPageId = $(this).parent().attr('id').split('-')[1];

                            // Update the item
                            $.ajax({
                                url: _config.baseUrl + '/api/v1/pages/' + newPageId + '/items/' + itemId,
                                dataType: 'json',
                                type: 'PUT',
                                success: function(data, status, request) {
                                    
                                    // Remove the item from this page
                                    ui.draggable.remove();
                                },
                                error: function(request, status, error) { console.log(error); }
                            });

                        }
                    });

                },
                error: function(request, status, error) { console.log(error); }
            });

            $('#content').on('click', '.edit-link', function(event){

                event.preventDefault();

                $('.edit-form').remove();
                $('.content').show();

                var item = $(this).parent().parent().parent(); // Get the parent item container of the clicked edit link

                var info = item.attr('id').split('-'); // Gives us a two-element array where index 0 is the item type and 1 is the ID

                var content = item.find('.content'); // Get the content of the item

                // TODO: Write function to serialise/deserialise content sections to/from models
                var model = { 
                    id: info[1], 
                    title: content.find('h1, h2').html(), 
                    body: content.find('p').html(),
                    list: item.data('list')
                };

                item.append(Mustache.render(_config.forms[info[0]], model));

                content.hide();

            });

            $('#content').on('submit', '.edit-form', function(event){

                event.preventDefault();

                var form = $(this);

                // Gives us a three-element array where index 1 is the item type and 2 is the ID
                var info = form.attr('id').split('-'); 

                var type = info[1];
                var id = parseInt(info[2], 10);

                var apiCall = '';
                var updateNav = false;
                var method = '';

                var model = {  
                    title: form.find('[name=title]').val(),
                    body: form.find('[name=body]').val(),
                    list: form.find('[name=list]').val()
                };

                switch(type)
                {
                    case 'item':
                        if(id === 0)
                        {
                            apiCall = '/items';
                            method = 'POST';
                        }
                        else
                        {
                            apiCall = '/items/' + id;
                            method = 'PUT';
                        }
                        break;
                    case 'listitem':
                        var parentId = form.parent().parent().attr('id').split('-')[1];
                        if(id === 0)
                        {
                            apiCall = '/items/' + parentId + '/items';
                            method = 'POST';
                        }
                        else
                        {
                            apiCall = '/items/' + parentId + '/items/' + id;
                            method = 'PUT';
                        }
                        break;
                    case 'page':
                        updateNav = true;
                        method = 'PUT';
                        break;
                }

                // Update the item
                $.ajax({
                    url: _config.baseUrl + '/api/v1/pages/' + _config.pageId + apiCall,
                    data: model,
                    dataType: 'json',
                    type: method,
                    success: function(data, status, request) {
                        
                        var item = form.parent();

                        item.find('> .content').children('.title').html(data.title);
                        item.find('> .content').children('.body').html('<p>' + data.body + '</p>');

                        item.attr('id', type + '-' + data.id);

                        item.find('ul').attr('id', 'list-' + data.id);
                        item.find('.add-listitem').show();

                        if(type == 'page')
                            $('#pagenav-' + id + ' a').html(data.title);

                        $('.edit-form').remove();
                        $('.content').show();

                        $("#items").sortable('refresh');

                        // In this case, sortable refresh doesn't seem to work for some reason,
                        // so we destroy the existing sortable (if there is one) and re-initialise
                        item.find(".item-list").sortable('destroy').sortable({
                            connectWith: ['.item-list'],
                            stop: updateListItemDisplayOrder,
                            handle: '.drag-link'
                        });

                        if(type === 'listitem')
                            updateListItemDisplayOrder();
                        else
                            updateItemDisplayOrder();

                    },
                    error: function(request, status, error) { console.log(error); }
                });

            });

            $('#add-page').on('click', function(event) {

                event.preventDefault();

                addNewPage();

            });

            $('#add-list').on('click', function(event) {

                event.preventDefault();

                addForm('list', $('#items'));

            });

            $('#add-note').on('click', function(event) {

                event.preventDefault();

                addForm('note', $('#items'));

            });

            $('#content').on('click', '.add-listitem', function(event) {

                event.preventDefault();

                addForm('listitem', $(this).parent().prev('ul'));

            });


            $('#content').on('click', '.delete-link', function(event) {

                event.preventDefault();

                var info = $(this).parent().parent().parent().attr('id').split('-');

                deleteItem(info[0], info[1]);

            });

        }

        function formSetup()
        {
            
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