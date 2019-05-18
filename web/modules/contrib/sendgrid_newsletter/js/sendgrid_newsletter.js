/**
 * @file
 */

(function ($) {
  'use strict';
  Drupal.ajax.prototype.specifiedResponse = function () {
    var ajax = this;

    if (ajax.ajaxing) {
      return false;
    }
    try {
      $.ajax(ajax.options);
    }
    catch (err) {
      alert('An error occurred while attempting to process ' + ajax.options.url);
      return false;
    }

    return false;
  };

  $(document).ready(function () {
    Drupal.ajax[$('a.ctools-use-modal').attr('href')].specifiedResponse();
  });
})(jQuery);
