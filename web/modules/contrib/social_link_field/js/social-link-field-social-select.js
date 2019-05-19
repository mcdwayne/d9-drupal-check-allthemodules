/**
 * @file
 * Social link select
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.SocialLinkFieldSelect = {
    attach: function () {
      $('.social-select').change(function () {
        var social = $(this).val();
        $(this).parent('.form-item').next('.form-item').find('.field-prefix')
          .text(drupalSettings['platforms'][social]['prefix']);
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
