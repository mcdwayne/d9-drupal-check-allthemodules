/**
 * @file
 * Declare Imager module Messages dialog - Drupal.imager.popups.messagesC.
 */

/*
 * Note: Variables ending with capital C or M designate Classes and Modules.
 * They can be found in their own files using the following convention:
 *   i.e. Drupal.imager.coreM is in file imager/js/imager.core.inc
 *        Drupal.imager.popups.baseC is in file imager/js/popups/imager.base.inc
 * Variables starting with $ are only used for jQuery 'wrapped sets' of objects.
 */

(function ($) {
  'use strict';

  if (localStorage.imagerDebugMessages === null) {
    localStorage.imagerDebugMessages = 'FALSE';
  }

  /**
   * Define messages dialog class - Drupal.imager.popups.messagesC.
   *
   * @param {object} spec
   *   Specifications for opening dialog, can also have ad-hoc properties
   *   not used by jQuery dialog but needed for other purposes.
   *
   * @return {dialog}
   *   The message dialog.
   */
  Drupal.imager.popups.messagesC = function messagesC(spec) {
    var Popups = Drupal.imager.popups;
    var popup;

    var dspec = $.extend({
      name: 'Messages',
      autoOpen: false,
      title: 'System messages  ',
      zIndex: 1015,
      width: 'auto',
      dialogClass: 'imager-dialog imager-messages-dialog',
      cssId: 'imager-messages',
      height: 'auto',
      resizable: false,
      open: function () {
        var closeBtn = $('.ui-dialog-titlebar-close');
        closeBtn.append('<span class="ui-button-icon-primary ui-icon ui-icon-closethick"></span><span class="ui-button-text">close</span>');
      },
      position: {
        my: 'left',
        at: 'right',
        of: spec.$selectButton
      }
    }, spec);
    // Initialize the popup.
    popup = Popups.baseC(dspec);

    popup.dialogOnCreate = function dialogOnCreate() {
      popup.dialogOpen();
    };

    popup.dialogOnOpen = function dialogOnOpen() {
      localStorage.imagerDebugMessages = 'TRUE';
      popup.dialogInit();
    };

    popup.dialogOnClose = function dialogOnClose() {
      localStorage.imagerDebugMessages = 'FALSE';
    };

    popup.dialogInit = function dialogInit() {
    };

    return popup;
  };
})(jQuery);
