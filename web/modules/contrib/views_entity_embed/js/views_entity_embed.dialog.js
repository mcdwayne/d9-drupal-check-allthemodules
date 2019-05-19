/**
 * @file
 * Provides JavaScript additions to entity embed dialog.
 *
 * This file provides popup windows for previewing embedded entities from the
 * embed dialog.
 */

(function ($, Drupal) {

  "use strict";

  /**
   * Attach behaviors to links for entities.
   */
  Drupal.behaviors.entityEmbedPreviewEntities = {
    attach: function (context) {
      $(context).find('form.views-entity-embed-select-dialog .form-item-entity a').on('click', Drupal.entityEmbedDialog.openInNewWindow);
    },
    detach: function (context) {
      $(context).find('form.views-entity-embed-select-dialog .form-item-entity a').off('click', Drupal.entityEmbedDialog.openInNewWindow);
    }
  };

  /**
   * Behaviors for the entityEmbedDialog iframe.
   */
  Drupal.behaviors.viewsEmbedDialog = {
    attach: function (context, settings) {
      $('body').once('js-views-embed-dialog').on('viewsBrowserIFrameAppend', function () {
        $('.views-entity-embed-select-dialog').trigger('resize');
        // Hide the next button, the click is triggered by Drupal.viewsEmbedDialog.selectionCompleted.
        $('#drupal-modal').parent().find('.js-button-next').addClass('visually-hidden');
      });
    }
  };

  /**
   * Entity Embed dialog utility functions.
   */
  Drupal.ViewEmbedDialog = Drupal.ViewEmbedDialog || {
    /**
     * Open links to entities within forms in a new window.
     */
    openInNewWindow: function (event) {
      event.preventDefault();
      $(this).attr('target', '_blank');
      window.open(this.href, 'entityPreview', 'toolbar=0,scrollbars=1,location=1,statusbar=1,menubar=0,resizable=1');
    },
    selectionCompleted: function (event, uuid, entities) {
      $('.views-entity-embed-select-dialog .js-button-next').click();
    }
  };

})(jQuery, Drupal);
