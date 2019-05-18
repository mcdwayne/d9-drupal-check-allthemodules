/**
 * @file
 * Create the file_entity Information dialog.
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

  if (localStorage.imagerShowInfo === null) {
    localStorage.imagerShowInfo = 'FALSE';
  }

  /**
   * Define the Information dialog class.
   *
   * @param {object} spec
   *   Specifications for opening dialog, can also have ad-hoc properties
   *   not used by jQuery dialog but needed for other purposes.
   *
   * @return {dialog}
   *  Return the information dialog.
   */
  Drupal.imager.popups.infoC = function infoC(spec) {
    var Popups = Drupal.imager.popups;
    var Viewer = Drupal.imager.viewer;
    var popup;
    var editField;

    var dspec = $.extend({
      name: 'Info',
      title: 'Information',
      zIndex: 1015,
      cssId: 'imager-info',
      resizable: false,
      draggable: true,
      position: {
        left: '50px',
        top: '20px'
      },
      open: function () {
        var closeBtn = $('.ui-dialog-titlebar-close');
        closeBtn.append('<span class="ui-button-icon-primary ui-icon ui-icon-closethick"></span><span class="ui-button-text">close</span>');
      },
      create: function () {
        $(this).closest('div.ui-dialog')
          .find('.ui-dialog-titlebar-close')
          .click(function (e) {
            popup.dialogClose();
            e.preventDefault();
          });
      }
    }, spec);
    // Initialize the popup.
    popup = Popups.baseC(dspec);

    popup.onButtonClick = function onButtonClick(buttonName) {
      switch (buttonName) {
        case 'imager-info-close':
          popup.dialogClose();
          break;
      }
    };
    popup.dialogOnCreate = function dialogOnCreate() {
      popup.dialogOpen();
    };

    popup.dialogOnOpen = function dialogOnOpen() {
      popup.dialogUpdate();
      localStorage.imagerShowInfo = 'TRUE';
    };

    popup.dialogOnClose = function dialogOnClose() {
      localStorage.imagerShowInfo = 'FALSE';
    };

    /**
     * Initialize checkboxes from localStorage
     */
    popup.dialogInit = function dialogInit() {
    };

    popup.dialogUpdate = function dialogUpdate() {
      Popups.$busy.show();
      Drupal.imager.core.ajaxProcess(
        this,
        Drupal.imager.settings.actions.displayEntity.url,
        {
          action: 'view-info',
          uri: Viewer.getImage().src,
          mid: Viewer.getImage().mid
        }, function (response) {
          Popups.$busy.hide();
          popup.$elem.removeClass('error').show();
          if (response['html']) {
            $('#imager-info-content').html(response['html']);
            $('.imager-info-edit').click(function (evt) {
              editField = this.id.replace('imager-', '');
              Popups.edit.dialogSelect({
                editField: editField,
                $selectButton: $(this)
              });
            });
          }
        }
      );
    };

    return popup;
  };
})(jQuery);
