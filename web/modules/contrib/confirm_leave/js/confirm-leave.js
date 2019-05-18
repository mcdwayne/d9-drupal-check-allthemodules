/**
 * @file
 * confirm-leave.js
 */

(function ($, Drupal) {

  'use strict';

  /**
   * If any input in the form is modified, add a confirm message on unload.
   *
   * @type {Object}
   */
  Drupal.behaviors.confirmLeave = {
    attach: function (context, settings) {
      $('.form-item').on('formUpdated', function (e) {
        $('form').addClass('form-updated');

        window.onbeforeunload = function (e) {
          let dialogText = 'Are you sure?';
          e.returnValue = dialogText;
          return dialogText;
        }

        $('#edit-submit').on('click', function (e) {
          window.onbeforeunload = null;
        });
      });
    }
  };

}(jQuery, Drupal));
