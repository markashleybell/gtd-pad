var GTDPad = (function($, window, undefined) {

    var _defaultAjaxErrorCallback = function (request, status, error) {
        alert('Http Error: ' + status + ' - ' + error);
    };

    var _ajaxPost = function (url, data, successCallback, errorCallback, contentType) {

        if (typeof errorCallback === 'undefined' || errorCallback === null) {
            errorCallback = _defaultAjaxErrorCallback;
        }

        var options = {
            url: url,
            data: data,
            dataType: 'json',
            type: 'POST'
        };

        if(typeof contentType !== 'undefined') {
            options.contentType = contentType;
        }

        return $.ajax(options).done(successCallback).fail(errorCallback);

    };

    var init = function() {
        // _ajaxPost('')
    };

    return {
        init: init
    };

}(jQuery, window, undefined));

$(function(){

    GTDPad.init();

});