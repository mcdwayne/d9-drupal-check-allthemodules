/**
* @file
* Provides JavaScript to handle contact form summary.
*/

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
  * Attach contact form summary handler.
  *
  * @type {Drupal~behavior}
  *
  * @prop {Drupal~behaviorAttach} attach
  *   Attaches form summary handler.
  */
  Drupal.behaviors.contactFormSummary = {
    attach : function (context, settings) {
      $(window, context).once('contactFormSummary').on('load', function () {
        var modal = $('#contact-form-summary-modal');
        var body = $('body');

        // Add open class only when ready (in case we want to animate through css)
        body.addClass('modal-open');
        modal.addClass('in');

        // When click on close, remove open class then wait a little before delete modal from dom.
        modal.find('[data-dismiss="modal"]').on('click', function () {
          body.removeClass('modal-open');
          modal.removeClass('in');
          setTimeout(function () {
            modal.remove();
          }, 1000);
        });

      });
    }
  };

})(jQuery, Drupal, drupalSettings);
