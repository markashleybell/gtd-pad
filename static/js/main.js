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
            page: null
        };

    var _defaultAjaxErrorCallback = function (request, status, error) {
        alert('Http Error: ' + status + ' - ' + error);
    };

    var _ajaxRequest = function (type, url, data, successCallback, errorCallback) {
        // If no error callback is explicitly supplied, pass in the default error callback
        if (typeof errorCallback === 'undefined' || errorCallback === null) {
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

    var _loadPage = function(id) {
        _pageId = id;
        // Load all the data for this page, including child items and grandchild listitems
        _ajaxGet('/pages/' + _pageId + '?children=true', null, function(data, status, request) { 
            var page = data.payload;
            _ui.pageContainer.html(_templates.page(page));
        });
    };

    var _loadPagesMenu = function(callback) {
        // Load the list of all pages and populate the sidebar menu
        _ajaxGet('/pages', null, function(data, status, request) { 
            _ui.pagesMenu.html(_templates.pagesMenu(data));
            if(typeof callback !== 'undefined') {
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
        // Handle page menu item click
        _ui.pagesMenu.on('click', 'a', function(e) {
            e.preventDefault();
            var a = $(this);
            var id = a.data('pageid');
            var title = a.text();
            History.pushState({ id: id, title: title }, title, '/' + id);
        });
        // Bind to History.js state change
        $(window).bind('statechange', function() {
            // Log the state
            var state = History.getState(); 
            // console.log('statechange:', state.data, state.title, state.url);
            _loadPage(state.data.id);
        });
        // Initially load the pages menu
        _loadPagesMenu(function() {
            // Load the page automatically once the menu has loaded
            _ui.pagesMenu.find('a[data-pageid=' + id + ']').trigger('click');
        });

        _loadPage(id);
    };

    return {
        init: _init
    };

}(jQuery, window, undefined, History, Handlebars));