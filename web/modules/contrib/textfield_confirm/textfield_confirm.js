/**
 * @file
 * Defines Javascript behaviors for the textfield_confirm module.
 */

(function (Drupal) {

  "use strict";

  /**
   * Attaches the behavior to each input element.
   */
  Drupal.behaviors.textfieldConfirm = {
    attach: function (context, settings) {
      var inputs = context.querySelectorAll('input.textfield-confirm-field:not(.textfield-confirm-processed)');

      for (var i = 0; i < inputs.length; ++i) {
        var input = inputs[i];
        input.className += ' textfield-confirm-processed';
        var success =  input.getAttribute('data-textfield-confirm-success');
        var error =  input.getAttribute('data-textfield-confirm-error');
        Drupal.textfieldConfirm.processInput(input, success, error);
      }
    }
  };

  Drupal.textfieldConfirm = Drupal.textfieldConfirm || {};

  /**
   * Adds checking behavior to textfield input elements.
   */
  Drupal.textfieldConfirm.processInput = function (primary, success, error) {
    var secondary = primary.parentNode.parentNode.querySelector('input.textfield-confirm-confirm');

    var helpText = document.createElement('span');
    helpText.className = 'textfield-confirm-help-text';
    secondary.parentNode.insertBefore(helpText, secondary.nextSibling);

    // Checks that the text fields are equal.
    function textfieldConfirmCheck() {
      // Verify that there is a value to check.
      if (primary.value === '') {
        helpText.innerHTML = '';
        return;
      }

      var message = primary.value === secondary.value ? success : error;

      if (helpText.innerHTML !== message) {
        helpText.innerHTML = message;
      }
    };

    // Monitor input events.
    primary.addEventListener('input', textfieldConfirmCheck);
    secondary.addEventListener('input', textfieldConfirmCheck);
  };

})(Drupal);
