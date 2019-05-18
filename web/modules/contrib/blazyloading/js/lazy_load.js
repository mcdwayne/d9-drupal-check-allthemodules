(function ($, window, Drupal, drupalSettings) {
  'use strict';
  Drupal.behaviors.jsvalidation = {
    attach: function (context, settings) {
      new Blazy({
        cdnServerStatus: drupalSettings.cdn_server_status,
        cdnServerUrl: drupalSettings.cdn_server_url,
        src: 'data-src',
        success: function (element) {
          element.className = element.className.replace('lazy_load_image', '');
          setTimeout(function () {
            var parent = element.parentNode;
            parent.className = parent.className.replace(/\bloading\b/, '');
          }, 500);
        },
        error: function (ele, msg) {
          ele.removeAttribute('src');
          ele.className = ele.className.replace('lazy_load_image', '');
        }
      });
    }
  };
})(jQuery, window, Drupal, drupalSettings);
