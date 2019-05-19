/**
 * @file
 * Drupal behavior for Skillset Inview -- percent visual aid.
 */

(function ($, Drupal) {
  'use strict';

  /**
   * Attach behavior for the Overview and Add forms.
   */
  Drupal.behaviors.skillsetInviewPercentAssist = {
    attach: function (context) {

      $('.percent-column input').once('skillsetPercentAssist').on('input change', function (e) {
        var newv = $(this).val();
        $(this).next().find('.visual-assist').text(newv);
      });

    }
  };

}(jQuery, Drupal));
