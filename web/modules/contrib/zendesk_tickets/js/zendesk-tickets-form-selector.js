/**
 * @file
 * Zendesk Tickets form selector.
 */

(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.zendeskTicketsFormSelector = {
    attach: function(context, settings) {
      $('.zendesk-tickets-form-selector .dropbutton-action--selected', context).once('zendesk-tickets')
        .closest('.dropbutton-action')
          .click(dropbuttonSelectedClickHandler);
    }
  };

  /**
   * Delegated callback for opening and closing dropbutton selected actions.
   *
   * @param {jQuery.Event} e
   *   The event triggered.
   *
   * @see Drupal.DropButton~dropbuttonClickHandler
   */
  function dropbuttonSelectedClickHandler(e) {
    e.preventDefault();
    $(e.target).closest('.dropbutton-wrapper').toggleClass('open');
  }

})(jQuery, Drupal);
