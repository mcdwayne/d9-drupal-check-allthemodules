/**
 * @file
 * confirm_leave.js
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Adds a confirmation message on unload.
   *
   * @type {Object}
   */
  Drupal.behaviors.commerceConfirmLeave = {
    attach: function (context, settings) {
      // @todo review .page selector
      $(context).find('.page').once('commerceConfirmLeave').each(function(){
        // Allow submit buttons.
        var submit = false;
        // @todo review :submit selector
        // @todo handle empty basket
        // @todo handle same page refresh
        $("input[type='submit']").each(function () {
          $(this).click(function () {
            submit = true;
          });
        });

        window.addEventListener("beforeunload", function (e) {
          if (!submit) {
            // @todo review error message
            var confirmationMessage = settings.commerce_confirm_leave.confirmation_message;
            e.returnValue = confirmationMessage;     // Gecko, Trident, Chrome 34+
            return confirmationMessage;              // Gecko, WebKit, Chrome <34
          }
        });

      });
    }
  };

}(jQuery, Drupal));
