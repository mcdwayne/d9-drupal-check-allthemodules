/**
 * @file
 * JavaScript behaviors for Ajax.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * @todo description.
   */
  Drupal.AjaxCommands.prototype.modalEntityFormScrollTop = function (ajax, response) {
    $('#drupal-modal').animate({
      scrollTop: 0
    }, 20);
  };


})(jQuery, Drupal);
