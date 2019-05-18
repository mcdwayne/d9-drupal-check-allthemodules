/**
 * @file
 * Provides additional javascript for managing the paragraph embedding dialog.
 */

(function($) {
  'use strict';

  /**
   * Attach behavior for passing dialog data from the iframe to the parent.
   *
   * We show the paragraph add/edit dialog inside iframe and this function is
   * passing data on closing of the form from the iframe to its parent window.
   */
  $.fn.ParagraphEditorDialogSaveAndCloseModalDialog = function(data) {
    // Save the editor dialog.
    window.parent.Drupal.AjaxCommands.prototype.editorDialogSave(
      {},
      { values: { attributes: data } }
    );

    // Close the modal dialog.
    window.parent.Drupal.AjaxCommands.prototype.closeDialog(
      {},
      { persist: false, selector: '#drupal-modal' }
    );
  };

})(jQuery);
