var GTDPad = (function($, window, undefined, History, Handlebars) {
    // Private member variables
    var _apiBaseUrl = '/api/v1', // API versioning
        _pageId = null, // ID of the currently loaded page
        _log = [], // Array to hold log info
        // Container for cached HTML selectors
        _ui = { 
            pagesMenu: null,
            pageContainer: null
        },
        // Container for compiled Handlebars templates
        _templates = {
            pagesMenu: null,
            page: null,
            item: null,
            listItem: null,
            pageForm: null,
            itemForm: null,
            listItemForm: null
        };
    // Given an HTML form, build an object literal with a 
    // property for each form field set to that field's value
    var _serializeToObjectLiteral = function($form) {
        var o = {};
        var a = $form.serializeArray();
        $.each(a, function() {
            if (o[this.name] !== undefined) {
                if (!o[this.name].push) {
                    o[this.name] = [o[this.name]];
                }
                o[this.name].push(this.value || '');
            } else {
                o[this.name] = this.value || '';
            }
        });
        return o;
    };
    // Default error callback to be called in the event of an AJAX error
    var _defaultAjaxErrorCallback = function (request, status, error) {
        alert('Http Error: ' + status + ' - ' + error);
    };
    // Base function 
    var _ajaxRequest = function (type, url, data, successCallback, errorCallback) {
        // If no error callback is explicitly supplied, pass in the default error callback
        if (errorCallback === null || typeof errorCallback !== 'function') {
            errorCallback = _defaultAjaxErrorCallback;
        }
        // Set up the AJAX request
        var options = {
            url: _apiBaseUrl + url,
            contentType: 'application/json; charset=utf-8',
            // Turn the data (if present) into a JSON string, as all our requests
            // are being sent as JSON (see contentType above)
            data: (data === null) ? null : JSON.stringify(data),
            // Expect the response to be JSON as well
            dataType: 'json', 
            type: type
        };
        // Perform the request
        $.ajax(options).done(successCallback).fail(errorCallback);
    };
    // Perform a GET request
    var _ajaxGet = function (url, data, successCallback, errorCallback) {
        _ajaxRequest('GET', url, data, successCallback, errorCallback);
    };
    // Perform a POST request
    var _ajaxPost = function (url, data, successCallback, errorCallback) {
        _ajaxRequest('POST', url, data, successCallback, errorCallback);
    };
    // Perform a PUT request
    var _ajaxPut = function (url, data, successCallback, errorCallback) {
        _ajaxRequest('PUT', url, data, successCallback, errorCallback);
    };
    // Perform a PATCH request
    var _ajaxPatch = function (url, data, successCallback, errorCallback) {
        _ajaxRequest('PATCH', url, data, successCallback, errorCallback);
    };
    // Perform a DELETE request
    var _ajaxDelete = function (url, data, successCallback, errorCallback) {
        _ajaxRequest('DELETE', url, data, successCallback, errorCallback);
    };
    // Load all the data for this page, including child items and grandchild listitems
    var _loadPage = function(id, callback) {
        _ajaxGet('/pages/' + id + '?children=true', null, function(data, status, request) { 
            var page = data.payload;
            // Set global _pageId to the ID of the page we've just loaded
            _pageId = id;
            _ui.pageContainer.html(_templates.page(page));
            // Set up item sorting
            _ui.pageContainer.find('div.items').sortable({
                stop: function(e, ui) {
                    // Create an array of object literals containing ID and 
                    // displayorder from the <div> elements
                    var order = _ui.pageContainer.find('div.items > div').map(function(i, item) { 
                        return { 
                            id: $(item).data('itemid'),
                            displayorder: i
                        };
                    }).get();
                    _ajaxPut('/pages/' + id + '/items', order, function(data, status, request) { 
                        // We don't need to do anything here
                    });
                },
                handle: '.drag'
            });
            _ui.pageContainer.find('div.content ul').sortable({
                stop: function(e, ui) {
                    var el = $(this);
                    // Create an array of object literals containing ID and 
                    // displayorder from the <div> elements
                    var order = el.find('li').map(function(i, item) { 
                        return { 
                            id: $(item).data('listitemid'),
                            displayorder: i
                        };
                    }).get();
                    _ajaxPut('/pages/' + id + '/items/' + order[0].id + '/listitems', order, function(data, status, request) { 
                        // We don't need to do anything here
                    });
                },
                handle: '.drag-item'
            });
            if(typeof callback === 'function') {
                callback();
            }
        });
    };
    // Load the list of all pages and populate the sidebar menu
    var _loadPagesMenu = function(callback) {
        _ajaxGet('/pages', null, function(data, status, request) { 
            _ui.pagesMenu.html(_templates.pagesMenu(data));
            // Set up page sorting
            _ui.pagesMenu.find('div.content > ul').sortable({
                stop: function(e, ui) {
                    // Create an array of object literals containing ID and 
                    // displayorder from the <li> elements
                    var order = _ui.pagesMenu.find('div.content > ul > li').map(function(i, item) { 
                        return { 
                            id: $(item).find('> a').data('pageid'),
                            displayorder: i
                        };
                    }).get();
                    _ajaxPut('/pages', order, function(data, status, request) { 
                        // We don't need to do anything here
                    });
                },
                handle: '.drag'
            });
            if(typeof callback === 'function') {
                callback();
            }
        });
    };
    // Set up the application
    var _init = function(id) {
        // Register a Handlebars helper to determine whether an item is a list
        Handlebars.registerHelper('islist', function(options) {
            return (this.itemtype_id == 1) ? options.fn(this) : options.inverse(this)
        });
        // Register partial views
        Handlebars.registerPartial('item', $('#item-template').html());
        Handlebars.registerPartial('listitem', $('#listitem-template').html());
        // Cache some selectors
        _ui.pagesMenu = $('#sidebar');
        _ui.pageContainer = $('#page-container');
        // Compile Handlebars templates
        _templates.pagesMenu = Handlebars.compile($('#pages-menu-template').html());
        _templates.page = Handlebars.compile($('#page-template').html());
        _templates.item = Handlebars.compile($('#item-template').html());
        _templates.listItem = Handlebars.compile($('#listitem-template').html());
        _templates.pageForm = Handlebars.compile($('#page-form-template').html());
        _templates.itemForm = Handlebars.compile($('#item-form-template').html());
        _templates.listItemForm = Handlebars.compile($('#listitem-form-template').html());
        // Handle page menu item click
        _ui.pagesMenu.on('click', 'li > a', function(e) {
            e.preventDefault();
            var a = $(this);
            var id = a.data('pageid');
            var title = a.text();
            History.pushState({ id: id, title: title }, title, '/' + id);
        });
        // Handle add page link click
        _ui.pagesMenu.on('click', 'a.add.page', function(e) {
            e.preventDefault();
            var a = $(this);
            var controls = a.parent();
            controls.after(_templates.pageForm({ displayorder: -1 })).hide();
        });
        // Handle new page form submit
        _ui.pagesMenu.on('submit', 'form.form-page', function(e) {
            e.preventDefault();
            var form = $(this);
            _ajaxPost('/pages', _serializeToObjectLiteral(form), function(data, status, request) { 
                _loadPagesMenu();
            });
        });
        // Handle page edit form submit
        _ui.pageContainer.on('submit', 'form.form-page', function(e) {
            e.preventDefault();
            var form = $(this);
            var id = form.data('pageid');
            var pageData = _serializeToObjectLiteral(form);
            _ajaxPut('/pages/' + id, pageData, function(data, status, request) { 
                _loadPagesMenu();
                form.prev('.controls').show();
                form.remove();
                $('#page-' + id + ' > .content > h1').html(pageData.title).show();
            });
        });
        // Handle page edit link click
        _ui.pageContainer.on('click', 'div.controls.page > a.edit', function(e) {
            e.preventDefault();
            var a = $(this);
            var id = a.data('pageid');
            var controls = a.parent();
            _ajaxGet('/pages/' + id, null, function(data, status, request) { 
                controls.after(_templates.pageForm(data.payload)).hide();
                $('#page-' + id + ' > .content > h1').hide();
            });
        });
        // Handle page delete link click
        _ui.pageContainer.on('click', 'div.controls.page > a.delete', function(e) {
            e.preventDefault();
            var a = $(this);
            _ajaxDelete('/pages/' + a.data('pageid'), null, function(data, status, request) { 
                window.location = '/';
            });
        });
        // Handle page add note/list link click
        _ui.pageContainer.on('click', 'div.controls.page > a.addnote, div.controls.page > a.addlist', function(e) {
            e.preventDefault();
            var a = $(this);
            var pageid = a.data('pageid');
            var controls = a.parent();
            // alert(id + ' List:' + a.hasClass('addlist'));
            controls.after(_templates.itemForm({
                id: 0,
                page_id: pageid,
                title: '',
                body: '',
                displayorder: -1,
                itemtype_id: (a.hasClass('addlist')) ? 1 : 2
            }));
        });
        // Handle item edit link click
        _ui.pageContainer.on('click', 'div.controls.item > a.edit', function(e) {
            e.preventDefault();
            var a = $(this);
            var id = a.data('itemid');
            var pageid = a.data('pageid');
            var controls = a.parent();
            _ajaxGet('/pages/' + pageid + '/items/' + id, null, function(data, status, request) { 
                controls.after(_templates.itemForm(data.payload)).hide();
                $('#item-' + id + ' > .content').hide();
            });
        });
        // Handle item edit form submit
        _ui.pageContainer.on('submit', 'form.form-item', function(e) {
            e.preventDefault();
            var form = $(this);
            var id = parseInt(form.data('itemid'), 10);
            var pageid = form.data('pageid');
            var itemData = _serializeToObjectLiteral(form);
            if(id === 0) { // New item
                _ajaxPost('/pages/' + pageid + '/items', itemData, function(data, status, request) { 
                    form.prev('.controls').show();
                    form.remove();
                    itemData.id = data.payload.id;
                    $('#page-' + pageid + ' > .content > .items').prepend(_templates.item(itemData));
                });
            } else { // Update item
                _ajaxPut('/pages/' + pageid + '/items/' + id, itemData, function(data, status, request) { 
                    form.prev('.controls').show();
                    form.remove();
                    $('#item-' + id).replaceWith(_templates.item(itemData));
                });
            }
        });
        // Handle item delete link click
        _ui.pageContainer.on('click', 'div.controls.item > a.delete', function(e) {
            e.preventDefault();
            var a = $(this);
            var id = parseInt(a.data('itemid'), 10);
            _ajaxDelete('/pages/' + a.data('pageid') + '/items/' + id, null, function(data, status, request) { 
                $('#item-' + id).remove();
            });
        });
        // Handle list item add link click
        _ui.pageContainer.on('click', 'div.controls.listitem > a.addlistitem', function(e) {
            e.preventDefault();
            var a = $(this);
            var pageid = parseInt(a.data('pageid'), 10);
            var itemid = parseInt(a.data('itemid'), 10);
            var controls = a.parent();
            // alert(id + ' List:' + a.hasClass('addlist'));
            controls.after(_templates.listItemForm({
                id: 0,
                page_id: pageid,
                item_id: itemid,
                body: '',
                displayorder: -1
            }));
            controls.hide();
        });
        // Handle list item cancel edit link click
        _ui.pageContainer.on('click', 'form a.cancel', function(e) {
            e.preventDefault();
            var a = $(this);
            a.parent().prev('.controls').show();
            a.parent().next('.content').show();
            a.closest('form').remove();
        });
        // Handle list item edit link click
        _ui.pageContainer.on('click', 'div.controls.listitem > a.edit', function(e) {
            e.preventDefault();
            var a = $(this);
            var id = a.data('listitemid');
            var itemid = a.data('itemid');
            var pageid = a.data('pageid');
            var controls = a.parent();
            _ajaxGet('/pages/' + pageid + '/items/' + itemid + '/listitems/' + id, null, function(data, status, request) { 
                controls.after(_templates.listItemForm(data.payload)).hide();
                $('#listitem-' + id + ' > .content').hide();
            });
        });
        // Handle list item edit form submit
        _ui.pageContainer.on('submit', 'form.form-listitem', function(e) {
            e.preventDefault();
            var form = $(this);
            var id = parseInt(form.data('listitemid'), 10);
            var itemid = parseInt(form.data('itemid'), 10);
            var pageid = parseInt(form.data('pageid'), 10);
            var itemData = _serializeToObjectLiteral(form);
            if(id === 0) { // New item
                _ajaxPost('/pages/' + pageid + '/items/' + itemid + '/listitems', itemData, function(data, status, request) { 
                    form.prev('.controls').show();
                    form.remove();
                    itemData.id = data.payload.id;
                    itemData.item_id = itemid;
                    itemData.page_id = pageid;
                    $('#item-' + itemid + ' div.content > ul').append(_templates.listItem(itemData));
                });
            } else { // Update item
                _ajaxPut('/pages/' + pageid + '/items/' + itemid + '/listitems/' + id, itemData, function(data, status, request) { 
                    form.prev('.controls').show();
                    form.remove();
                    itemData.id = id;
                    itemData.item_id = itemid;
                    itemData.page_id = pageid;
                    $('#listitem-' + id).replaceWith(_templates.listItem(itemData));
                });
            }
        });
        // Handle list item checkbox click
        _ui.pageContainer.on('click', 'input.listitem-complete', function(e) {
            var checkbox = $(this);
            var id = parseInt(checkbox.data('listitemid'), 10);
            var itemid = parseInt(checkbox.data('itemid'), 10);
            var pageid = parseInt(checkbox.data('pageid'), 10);
            var itemData = { completed: checkbox[0].checked };
            _ajaxPatch('/pages/' + pageid + '/items/' + itemid + '/listitems/' + id, itemData, function(data, status, request) { 
                _ajaxGet('/pages/' + pageid + '/items/' + itemid + '?children=true', null, function(data, status, request) { 
                    $('#item-' + itemid).replaceWith(_templates.item(data.payload));
                });
            });
        });
        // Handle list item delete link click
        _ui.pageContainer.on('click', 'div.controls.listitem > a.delete', function(e) {
            e.preventDefault();
            var a = $(this);
            var id = parseInt(a.data('listitemid'), 10);
            _ajaxDelete('/pages/' + a.data('pageid') + '/items/' + a.data('pageid') + '/listitems/' + id, null, function(data, status, request) { 
                $('#listitem-' + id).remove();
            });
        });
        // Initially load the pages menu
        _loadPagesMenu(function() {
            // Get the initial title from the page link to avoid an additional request
            var title = _ui.pagesMenu.find('a[data-pageid=' + id + ']').text();
            // Add the initial page to the history state
            History.pushState({ id: id, title: title }, title, '/' + id); 
            // Load the page
            _loadPage(id, function() {
                // Bind to History.js state change event, but only once the first page has 
                // loaded, as we've already manually updated the state for the initial page
                $(window).bind('statechange', function() {
                    // Log the state
                    var state = History.getState(); 
                    // Load the page
                    _loadPage(state.data.id);
                });
            });
        });
    };
    // Public methods
    return {
        init: _init
    };

}(jQuery, window, undefined, History, Handlebars));