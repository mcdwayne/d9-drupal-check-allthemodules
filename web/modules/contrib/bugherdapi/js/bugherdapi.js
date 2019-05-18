/**
 * @file
 * JS to init Bugherd with the proper api key.
 */

(function ($, Drupal) {

  'use strict';

  $(document).ready(function () {

      // Get the bugherd settings so we can extract the api key.
      var config = drupalSettings.bugherdapi;

      // Add bugherd embed script.
      (function (d, t) {
        var bh = d.createElement(t), s = d.getElementsByTagName(t)[0];
        bh.type = 'text/javascript';
        bh.src = '//www.bugherd.com/sidebarv2.js?apikey='+config.api_key;
        s.parentNode.insertBefore(bh, s);
      })(document, 'script');

  });
})(jQuery, Drupal);
