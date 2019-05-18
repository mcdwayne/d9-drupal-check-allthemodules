var CleverReach = window.CleverReach || {};

(function () {
  'use strict';

  const ajax = {
    x() {
      const versions = [
        'MSXML2.XmlHttp.6.0',
        'MSXML2.XmlHttp.5.0',
        'MSXML2.XmlHttp.4.0',
        'MSXML2.XmlHttp.3.0',
        'MSXML2.XmlHttp.2.0',
        'Microsoft.XmlHttp'
      ];
      let xhr;
      let i;

      if (typeof XMLHttpRequest !== 'undefined') {
        return new XMLHttpRequest();
      }

      for (i = 0; i < versions.length; i++) {
        try {
          xhr = new ActiveXObject(versions[i]);
          break;
        }
        catch (e) {
          throw e;
        }
      }

      return xhr;
    },

    send(url, callback, method, data, format, async) {
      const x = ajax.x();

      if (async !== true && async !== false) {
        async = true;
      }

      x.open(method, url, async);
      x.onreadystatechange = function () {
        if (x.readyState === 4) {
          let response = x.responseText;
          const status = x.status;

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

    post(url, data, callback, format, async) {
      const query = [];

      if (format === 'json') {
        ajax.send(url, callback, 'POST', JSON.stringify(data), format, async);
      }
      else {
        for (const key in data) {
          if (data.hasOwnProperty(key)) {
            query.push(`${encodeURIComponent(key)}=${encodeURIComponent(data[key])}`);
          }
        }

        ajax.send(url, callback, 'POST', query.join('&'), format, async);
      }
    },

    get(url, data, callback, format, async) {
      const query = [];
      for (const key in data) {
        if (data.hasOwnProperty(key)) {
          query.push(`${encodeURIComponent(key)}=${encodeURIComponent(data[key])}`);
        }
      }

      ajax.send(url + (query.length ? `?${query.join('&')}` : ''), callback, 'GET',
        null, format, async);
    }
  };

  /**
     * Ajax component
     */
  CleverReach.Ajax = ajax;
}());
