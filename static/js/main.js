var GTDPad = (function($, window, undefined) {

    var _apiBaseUrl = '/api/v1',
        _ui = {
            pagesMenu: null,
            pageContainer: null
        },
        _templates = {
            pagesMenu: null,
            page: null,
            item: null,
            listitem: null
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

    var init = function() {

        Handlebars.registerHelper('islist', function(id, options) {
            return (id === 1) ? options.fn(this) : options.inverse(this)
        });

        _ui.pagesMenu = $('#sidebar');
        _ui.pageContainer = $('#page-container');
        _templates.pagesMenu = Handlebars.compile($('#pages-menu-template').html());
        _templates.page = Handlebars.compile($('#page-template').html());

        Handlebars.registerPartial('item', $('#item-template').html());
        Handlebars.registerPartial('listitem', $('#listitem-template').html());

        // _ajaxGet('/pages', null, function(data, status, request) { 
        //     // console.log(data);
        //     _ui.pagesMenu.html(_templates.pagesMenu(data));
        //     $.each(data.payload, function(i, page) {
        //         // console.log(item);
        //         _ajaxGet('/pages/' + page.id + '/items', null, function(data, status, request) {
        //             $.each(data.payload, function(i, item) { 
        //                 // console.log(item);
        //                 // If it's a list, also load the items
        //                 if(item.itemtype_id === 1) {
        //                     _ajaxGet('/pages/' + page.id + '/items/' + item.id + '/listitems', null, function(data, status, request) {
        //                         $.each(data.payload, function(i, listitem) { 
        //                             console.log(listitem);
        //                         });
        //                     });
        //                 }
        //             });
        //         });
        //     });
        // });

        _ajaxGet('/pages', null, function(data, status, request) { 
            _ui.pagesMenu.html(_templates.pagesMenu(data));
            _ajaxGet('/pages/' + data.payload[0].id + '?children=true', null, function(data, status, request) { 
                _ui.pageContainer.html(_templates.page(data.payload));
            });
        });
    };

    return {
        init: init
    };

}(jQuery, window, undefined));

$(function(){

    GTDPad.init();

});