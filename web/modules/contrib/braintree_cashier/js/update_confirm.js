/**
 * @file
 * Support the update subscription confirmation form.
 */
(function ($, Drupal) {

  'use strict';

  var form;
  var confirmButton;

  /**
   * Callback for the form submit event. Prevent duplicate form submission.
   *
   * @param {jQuery.Event} event
   */
  function onFormSubmit(event) {
    confirmButton.prop('disabled', true)
      .addClass('is-disabled');
  }

  /**
   * Attach the click handler to the confirm button.
   *
   * @type {{attach: Drupal.behaviors.signupForm.attach}}
   */
  Drupal.behaviors.braintreeCashierUpdateConfirm = {
    attach: function (context, settings) {
      $('form', context).once('initializeUpdateConfirm').each(function() {
        form = $(this);
        confirmButton = $('#submit-button');

        // Bind to form submit.
        form.submit(onFormSubmit);
      });
    }
  };

})(jQuery, Drupal);