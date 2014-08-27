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

    var _serializeObject = function($form) {
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
            _ajaxPost('/pages', _serializeObject(form), function(data, status, request) { 
                _loadPagesMenu();
            });
        });
        // Handle page edit form submit
        _ui.pageContainer.on('submit', 'form.form-page', function(e) {
            e.preventDefault();
            var form = $(this);
            var id = form.data('pageid');
            var pageData = _serializeObject(form);
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
            var itemData = _serializeObject(form);
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
        // Handle list item add link click
        _ui.pageContainer.on('click', 'form a.cancel', function(e) {
            e.preventDefault();
            var a = $(this);
            a.parent().prev('.controls').show();
            a.closest('form').remove();
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

    return {
        init: _init
    };

}(jQuery, window, undefined, History, Handlebars));