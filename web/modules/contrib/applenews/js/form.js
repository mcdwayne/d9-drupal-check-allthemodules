/**
 * @file
 * Apple News individual entity vertical tab helper.
 */

(function ($, window, Drupal) {

  Drupal.behaviors.AppleNewsEntityForm = {
    attach: function attach() {
      if (typeof $.fn.drupalSetSummary === 'undefined') {
        return;
      }

      $('.applenews-sections').parent().css({'margin-left' : '20px'});

    }
  };

}(jQuery, window, Drupal));
