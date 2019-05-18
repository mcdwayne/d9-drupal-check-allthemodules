/**
 * @file
 * Exchanges the URL on redirectLess responses.
 */
(function ($, drupalSettings) {

  'use strict';

  $(document).ready(function () {
    var redirectless_url = drupalSettings.redirectLessUrl;
    if (redirectless_url) {
      window.history.pushState(null, null, redirectless_url);
    }
  });

})(jQuery, drupalSettings);
