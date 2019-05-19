/**
 * @file
 * Code for the supercookie module.
 */

(function ($, Drupal, drupalSettings, navigator, document, storageLocal, storageSession) {

  'use strict';

  Drupal.behaviors.supercookie = {
    entitiesTracked: false,
    attach: function (context) {
      // Honor user's DNT header.
      if (drupalSettings.supercookie.dnt === true) {
        return;
      }
      if ($.isEmptyObject(navigator)) {
        return;
      }

      // Add request header to all AJAX requests.
      $.ajaxSetup({
        global: true,
        beforeSend: function (xhr, options) {
          if (options.url.indexOf(drupalSettings.supercookie.json) < 0) {
            if (!drupalSettings.supercookie.hash) {
              // This condition occurs when the original writeCookie() call
              // below fails due to high security browser settings; we'll assume
              // the user still has JavaScript enabled, though and force our
              // headers upon them.
              Drupal.behaviors.supercookie.failOver(xhr, options);
            }
            else {
              xhr.setRequestHeader(drupalSettings.supercookie.name_header, drupalSettings.supercookie.hash);
              if (options.headers) {
                options.headers[drupalSettings.supercookie.name_header] = drupalSettings.supercookie.hash;
              }
            }
          }
        }
      });

      this
        .fingerprint()
        .then(function (result) {
          return result.callback;
        })
        .then(this.writeCookie);
    },
    failOver: function (xhr, xhrOptions) {
      // Kill current AJAX request.
      xhr.abort();

      // Force a new supercookie request first.
      this
        .fingerprint()
        .then(function (result) {
          return result.callback;
        })
        .then(this.writeCookie)
        .then(function (data) {
          // Force the supercookie request header on to the original request.
          xhrOptions.headers[drupalSettings.supercookie.name_header] = data.hash;
          // Now send the original (manipulated) request.
          $.ajax(xhrOptions);
        });
    },
    fingerprint: function () {

      // Loop navigator object and fingerprint this user.
      var data = {};
      for (var member in navigator) {
        // 2016-04-01, Tory:
        // Firefox - a private browsing window does not have this property on
        // navigator object, while a normal window does. Bypass it so our
        // fingerprint is not different between the two window modes.
        // @see https://bugzilla.mozilla.org/show_bug.cgi?id=1112136
        if (member === 'serviceWorker') {
          continue;
        }

        switch (typeof navigator[member]) {
          case 'object':
          case 'string':
          case 'boolean':
            data[member] = navigator[member];
            break;
        }
      }
      // Do deep recursion on navigator data collected.
      data = JSON.prune(data);
      // Set hash of data string.
      var hash = CryptoJS.MD5(data);
      // Get local date/time. TODO: NOTE SAFARI AND IE'S MISHANDLING OF ECMA DATE OBJECT FORMAT!!!!
      var date = new Date($.now());
      date = date.toLocaleString();
      date = encodeURIComponent(date);

      var url = drupalSettings.supercookie.json + '?client=' + hash + '&date=' + date;
      if (!this.entitiesTracked) {
        url += '&ref=' + document.location.pathname;
      }

      // Do not attempt to geolocate users.
      if (!drupalSettings.supercookie.geolocation) {
        var deferred = $.Deferred();
        deferred.resolve({
          callback: url
        });
        return deferred.promise();
      }

      // Now attempt to append geolocation coords to url and return.
      var getCurrentPositionDeferred = function (options) {
        var deferred = $.Deferred();
        // Note the 2nd deferred.resolve arg here (vs. deferred.reject). We want
        // this to always "succeed" and eval result (which could be a
        // PositionError or Geoposition object) in the single done() callback.
        navigator.geolocation.getCurrentPosition(deferred.resolve, deferred.resolve, options);
        return deferred.promise();
      };

      return getCurrentPositionDeferred({
        enableHighAccuracy: true
      })
        .done(function (result) {
          if (result.coords) {
            url += '&geo=' + result.coords.latitude + ',' + result.coords.longitude;
          }
          result.callback = url;
          return result;
        });
    },
    writeCookie: function (url) {

      var xhrCookie = {
        method: 'GET',
        url: url,
        beforeSend: function (xhr, options) {
          xhr.setRequestHeader(drupalSettings.supercookie.name_header, drupalSettings.supercookie.hash);
          xhr.setRequestHeader('Accept', 'application/json');

          // Ensures that the "ref" query param is only sent to the server once.
          Drupal.behaviors.supercookie.entitiesTracked = true;
        },
        complete: function (xhr) {
          if (xhr.status === 200) {
            // Set client-side cookie + localStorage.
            var response = (xhr.responseJSON ? xhr.responseJSON : JSON.parse(xhr.responseText));
            var expires = new Date(response.expires * 1000);

            document.cookie = drupalSettings.supercookie.name_server + '=""; expires=-1';
            document.cookie = drupalSettings.supercookie.name_server + '=' + response.hash + '; expires=' + expires.toGMTString() + '; path=/';

            if (storageLocal) {
              storageLocal.setItem(drupalSettings.supercookie.name_server, response.hash);
            }
            if (storageSession) {
              storageSession.setItem(drupalSettings.supercookie.name_server, response.hash);
            }

            drupalSettings.supercookie.scid = response.scid;
            drupalSettings.supercookie.hash = response.hash;

            return response.hash;
          }
        }
      };

      // Get server response.
      return $.ajax(xhrCookie);
    }
  };

})(jQuery, Drupal, drupalSettings, window.navigator, document, window.localStorage, window.sessionStorage);
