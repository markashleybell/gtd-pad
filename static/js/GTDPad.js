var GTDPad = (function($, window, undefined, History, Handlebars) {
    // Private member variables
    var _apiBaseUrl = '/api/v1', // API versioning
        _pageId = null, // ID of the currently loaded page
        // Container for cached HTML selectors
        _ui = { 
            pagesMenu: null,
            pageContainer: null
        },
        // Container for compiled Handlebars templates
        _templates = {
            pagesMenu: null,
            page: null,
            pageForm: null,
            itemForm: null,
            listItemForm: null
        };

    _serializeObject = function($form) {
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

    var _defaultAjaxErrorCallback = function (request, status, error) {
        alert('Http Error: ' + status + ' - ' + error);
    };

    var _ajaxRequest = function (type, url, data, successCallback, errorCallback) {
        // If no error callback is explicitly supplied, pass in the default error callback
        if (errorCallback === null || typeof errorCallback !== 'function') {
            errorCallback = _defaultAjaxErrorCallback;
        }

        var options = {
            url: _apiBaseUrl + url,
            data: (data === null) ? null : JSON.stringify(data),
            dataType: 'json',
            type: type,
            contentType: 'application/json; charset=utf-8'
        };

        $.ajax(options).done(successCallback).fail(errorCallback);
    };

    var _ajaxGet = function (url, data, successCallback, errorCallback) {
        _ajaxRequest('GET', url, data, successCallback, errorCallback);
    };

    var _ajaxPost = function (url, data, successCallback, errorCallback) {
        _ajaxRequest('POST', url, data, successCallback, errorCallback);
    };

    var _ajaxPut = function (url, data, successCallback, errorCallback) {
        _ajaxRequest('PUT', url, data, successCallback, errorCallback);
    };

    var _ajaxDelete = function (url, data, successCallback, errorCallback) {
        _ajaxRequest('DELETE', url, data, successCallback, errorCallback);
    };

    var _loadPage = function(id, callback) {
        _pageId = id;
        // Load all the data for this page, including child items and grandchild listitems
        _ajaxGet('/pages/' + _pageId + '?children=true', null, function(data, status, request) { 
            var page = data.payload;
            _ui.pageContainer.html(_templates.page(page));
            if(typeof callback === 'function') {
                callback();
            }
        });
    };

    var _loadPagesMenu = function(callback) {
        // Load the list of all pages and populate the sidebar menu
        _ajaxGet('/pages', null, function(data, status, request) { 
            _ui.pagesMenu.html(_templates.pagesMenu(data));
            if(typeof callback === 'function') {
                callback();
            }
        });
    };

    var _init = function(id) {
        // Register Handlebars helper to determine whether an item is a list
        Handlebars.registerHelper('islist', function(options) {
            return (this.itemtype_id === 1) ? options.fn(this) : options.inverse(this)
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
        _templates.pageForm = Handlebars.compile($('#page-form-template').html());
        // Handle page menu item click
        _ui.pagesMenu.on('click', 'li > a', function(e) {
            e.preventDefault();
            var a = $(this);
            var id = a.data('pageid');
            var title = a.text();
            History.pushState({ id: id, title: title }, title, '/' + id);
        });
        // Handle add page link click
        _ui.pagesMenu.on('click', 'a.add-link.page', function(e) {
            e.preventDefault();
            var a = $(this);
            var controls = a.parent();
            controls.after(_templates.pageForm({ displayorder: -1 })).hide();
        });
        // Handle new page form submit
        _ui.pagesMenu.on('submit', 'form.edit-form.page', function(e) {
            e.preventDefault();
            var form = $(this);
            _ajaxPost('/pages', _serializeObject(form), function(data, status, request) { 
                _loadPagesMenu();
            });
        });
        // Initially load the pages menu
        _loadPagesMenu(function() {
            // Get the initial title from the page link to avoid an additional request
            var title = _ui.pagesMenu.find('a[data-pageid=' + id + ']').text();
            // Add the initial page to the history state
            History.pushState({ id: id, title: title }, title, '/'); 
            console.log('initstatechange:', state.data, state.title, state.url);
            // Load the page
            _loadPage(id, function() {
                // Bind to History.js state change event, but only once the first page has 
                // loaded, as we've already manually updated the state for the initial page
                $(window).bind('statechange', function() {
                    // Log the state
                    var state = History.getState(); 
                    console.log('statechange:', state.data, state.title, state.url);
                    // Load the page
                    _loadPage(state.data.id);
                });
            });
        });

        var state = History.getState(); 
        console.log('initial:', state.data, state.title, state.url);
    };

    return {
        init: _init
    };

}(jQuery, window, undefined, History, Handlebars));