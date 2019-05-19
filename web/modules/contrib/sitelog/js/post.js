/**
 * @file
 * Send uncached POST request.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';
  $(document).ready(function () {
    $.ajax({
      type: 'POST',
      cache: false,
      url: drupalSettings.sitelog.url,
      data: drupalSettings.sitelog.data,
    });
  });
})(jQuery, Drupal, drupalSettings);
