var GTDPad = (function($, window, undefined) {

    var _apiBaseUrl = '/api/v1',
        _ui = {
            pagesMenu: null
        },
        _templates = {
            pagesMenu: null
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
        _ui.pagesMenu = $('#sidebar');
        _templates.pagesMenu = Handlebars.compile($('#pages-menu-template').html());

        _ajaxGet('/pages', null, function(data, status, request) { 
            console.log(data);
            _ui.pagesMenu.html(_templates.pagesMenu(data));
        });
    };

    return {
        init: init
    };

}(jQuery, window, undefined));

$(function(){

    GTDPad.init();

});