/**
 * @file
 * Defines Javascript behaviors for the bbr module.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Behaviors for bbr block form.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches Browser Back Button behavior for bbr block form.
   */
  Drupal.behaviors.bbr = {
    attach: function (context) {
      $('.form-item-bbr-field').css('display', 'none');
      var bbr = $('input[name="bbr_field"]').val();
      if (bbr === 'yes') {
        location.reload(true);
      }
      else {
        $('input[name="bbr_field"]').val('yes');
      }
    }
  };

})(jQuery, Drupal, drupalSettings);
