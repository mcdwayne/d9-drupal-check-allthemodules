/**
 * @file
 * Declare Imager module Confirmation dialog - Drupal.imager.popups.confirmC.
 */

/*
 * Note: Variables ending with capital C or M designate Classes and Modules.
 * They can be found in their own files using the following convention:
 *   i.e. Drupal.imager.coreM is in file imager/js/imager.core.inc
 *        Drupal.imager.popups.baseC is in file imager/js/popups/imager.base.inc
 * Variables starting with $ are only used for jQuery 'wrapped sets' of objects.
 */

/**
 * Wrap file in JQuery();.
 *
 * @param $
 */
(function ($) {
  'use strict';

  /**
   * Declare Confirmation Dialog class.
   *
   * @param {Object} spec
   *   Specifications for opening dialog, can also have ad-hoc properties
   *   not used by jQuery dialog but needed for other purposes.
   *
   * @return {Object}
   *   Reference to the popup.
   */
  Drupal.imager.popups.confirmC = function confirmC(spec) {
    var Core = Drupal.imager.core;
    var Viewer = Drupal.imager.viewer;

    var dspec = $.extend({
      name: 'Confirm',
      autoOpen: false,
      title: 'Delete Image confirmation',
      zIndex: 1018,
      width: 'auto',
      dialogClass: 'imager-confirm-dialog imager-noclose',
      cssId: 'imager-confirm',
      height: 'auto',
      resizable: false,
      position: {
        my: 'left',
        at: 'right',
        of: $('#file-delete')
      }
    }, spec);
    // Initialize the popup.
    var popup = Drupal.imager.popups.baseC(dspec);

    popup.dialogOnCreate = function dialogOnCreate() {
      popup.dialogOpen();
    };

    popup.dialogOnClose = function dialogOnClose() {
      Viewer.setEditMode('view');
    };

    popup.dialogUpdate = function dialogUpdate() {
    };

    popup.dialogOnOpen = function dialogOnOpen() {
      $('#imager-confirm-content').html('Are you sure you want to delete ' + 'file');
    };

    popup.deleteImage = function deleteImage() {
      Core.ajaxProcess(this,
        Drupal.imager.settings.actions.deleteFile.url,
        {
          action: 'delete-file',
          uri: Viewer.getImage().src
        }
      );
    };

    popup.spec['buttons'] = {
      Delete: popup.deleteImage,
      Cancel: popup.dialogClose
    };
    return popup;
  };

})(jQuery);
