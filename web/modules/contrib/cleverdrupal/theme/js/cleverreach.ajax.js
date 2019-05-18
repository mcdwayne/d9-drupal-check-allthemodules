var CleverReach = window['CleverReach'] || {};

(function () {
    'use strict';

    var ajax = {
        x: function () {
            var versions = [
                'MSXML2.XmlHttp.6.0',
                'MSXML2.XmlHttp.5.0',
                'MSXML2.XmlHttp.4.0',
                'MSXML2.XmlHttp.3.0',
                'MSXML2.XmlHttp.2.0',
                'Microsoft.XmlHttp'
            ];
            var xhr;
            var i;

            if (typeof XMLHttpRequest !== 'undefined') {
                return new XMLHttpRequest();
            }

            for (i = 0; i < versions.length; i++) {
                try {
                    xhr = new ActiveXObject(versions[i]);
                    break;
                } catch (e) {
                }
            }

            return xhr;

        },

        send: function (url, callback, method, data, format, async) {
            var x = ajax.x();

            if (async === undefined) {
                async = true;
            }

            x.open(method, url, async);
            x.onreadystatechange = function () {
                if (x.readyState === 4) {
                    var response = x.responseText;
                    var status = x.status;

                    if (format === 'json') {
                        response = JSON.parse(response);
                    }

                    if (callback) {
                        callback(response, status);
                    }
                }
            };

            if (method === 'POST') {
                x.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            }

            x.send(data);

        },

        post: function (url, data, callback, format, async) {
            var query = [];

            if (format === 'json') {
                ajax.send(url, callback, 'POST', JSON.stringify(data), format, async);
            } else {
                for (var key in data) {
                    if (data.hasOwnProperty(key)) {
                        query.push(encodeURIComponent(key) + '=' + encodeURIComponent(data[key]));
                    }
                }

                ajax.send(url, callback, 'POST', query.join('&'), format, async);
            }

        },

        get: function (url, data, callback, format, async) {
            var query = [];
            for (var key in data) {
                if (data.hasOwnProperty(key)) {
                    query.push(encodeURIComponent(key) + '=' + encodeURIComponent(data[key]));
                }
            }

            ajax.send(url + (query.length ? '?' + query.join('&') : ''), callback, 'GET',
                null, format, async);
        }
    };

    /**
     * Ajax component
     */
    CleverReach.Ajax = ajax;
})();