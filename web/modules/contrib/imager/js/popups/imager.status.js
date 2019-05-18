/**
 * @file
 * Declare Imager module Status dialog - Drupal.imager.popups.statusC.
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

  if (localStorage.imagerDebugStatus === null) {
    localStorage.imagerDebugStatus = 'FALSE';
  }

  /**
   * Declare Status dialog class.
   *
   * @param {object} spec
   *   Specifications for opening dialog, can also have ad-hoc properties
   *   not used by jQuery dialog but needed for other purposes.
   *
   * @return {dialog}
   *   The Status dialog.
   */
  Drupal.imager.popups.statusC = function statusC(spec) {
    var Popups = Drupal.imager.popups;
    var Viewer = Drupal.imager.viewer;
    var popup;

    var dspec = $.extend({
      name: 'Status',
      title: 'Imager Status',
      zIndex: 1015,
      dialogClass: 'imager-dialog imager-status-dialog',
      cssId: 'imager-status',
      resizable: false,
      draggable: true
    }, spec);
    // Initialize the popup.
    popup = Popups.baseC(dspec);

    popup.onButtonClick = function onButtonClick(buttonName) {
      switch (buttonName) {
        case 'imager-status-close':
          popup.dialogClose();
          break;
      }
    };

    popup.dialogOnCreate = function dialogOnCreate() {
      popup.dialogOpen();
    };

    popup.dialogOnOpen = function dialogOnOpen() {
      localStorage.imagerDebugStatus = 'TRUE';
      Viewer.updateStatus();
      Popups.brightness.updateStatus();
      Popups.color.updateStatus();
    };

    popup.dialogOnClose = function dialogOnClose() {
      localStorage.imagerDebugStatus = 'FALSE';
    };

    popup.dialogInit = function dialogInit() {
      // Query all other dialogs for their status.
    };

    popup.dialogUpdate = function dialogUpdate(status) {
      if (popup.dialogIsOpen()) {
        var key;
        for (key in status) {
          if (status.hasOwnProperty(key)) {
            $('#imager-status-' + key).html(status[key]);
          }
        }
      }
    };

    return popup;
  };
})(jQuery);
