/**
 * @file
 * Javascript for the Sticky Toolbar reset.
 */

(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.sticky_toolbar_reset = {
    attach: function () {
      var $toolbar_height = $('#toolbar-administration').height();
      $('body').removeClass('toolbar-fixed');
      if ($toolbar_height) {
        $('body').css('padding-top', $toolbar_height + 'px');
      }
    }
  };

})(jQuery, Drupal);
