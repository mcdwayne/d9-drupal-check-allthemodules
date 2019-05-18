/**
 * @file
 * Imager module brightness dialog.
 */

/*
 * Note: Variables ending with capital C or M designate Classes and Modules.
 * They can be found in their own files using the following convention:
 *   i.e. Drupal.imager.coreM is in file imager/js/imager.core.js
 *        Drupal.imager.popups.baseC is in file imager/js/popups/imager.base.js
 * Variables starting with $ are only used for jQuery 'wrapped sets' of objects.
 */

(function ($) {
  'use strict';

  /**
   * Define Brightness/Contrast Dialog class - Drupal.imager.popups.brightnessC.
   *
   * @param {object} spec
   *   Specifications for opening dialog, can also have ad-hoc properties
   *   not used by jQuery dialog but needed for other purposes.
   *
   * @return {object}
   *   Popup dialog reference.
   */
  Drupal.imager.popups.brightnessC = function brightnessC(spec) {
    var Popups = Drupal.imager.popups;
    var Viewer = Drupal.imager.viewer;
    var brightness = 0;
    var contrast = 0;

    var dspec = $.extend({
      name: 'Brightness',
      title: 'Brightness/Contrast',
      zIndex: 1015,
      cssId: 'imager-brightness',
      draggable: true,
      resizable: false,
      position: {
        left: '50px',
        top: '150px'
      }
    }, spec);

    var popup = Popups.baseC(dspec);

    popup.onButtonClick = function onButtonClick(buttonName) {
      switch (buttonName) {
        case 'imager-brightness-apply':
          Viewer.applyFilter(adjustBrightness);
          break;

        case 'imager-brightness-reset':
          init();
          if (popup.dialogIsOpen()) {
            $('#slider-brightness').val(0);
            $('#slider-contrast').val(0);
            adjustBrightness(Viewer.$canvas2, Viewer.$canvas);
          }
          break;

        case 'imager-brightness-cancel':
          Viewer.setEditMode('view');
          Viewer.redraw();
          popup.dialogClose();
          popup.updateStatus();
          break;
      }
    };

    popup.dialogOnCreate = function dialogOnCreate() {
      popup.dialogOpen();
    };

    popup.dialogOnOpen = function dialogOnOpen() {
      Viewer.setInitialImage();
      popup.dialogInit();
    };

    // @TODO - this will fail if the dialog is being closed because another is being opened.
    // Add a report variable to both open and close.
    popup.dialogOnClose = function dialogOnClose() {
    };

    function init() {
      brightness = 0;
      contrast = 0;
      popup.updateStatus();
    }

    /**
     * Initialize checkboxes from localStorage
     */
    popup.dialogInit = function dialogInit() {
      init();
      $('#slider-brightness').change(function () {
        adjustBrightness(Viewer.$canvas2, Viewer.$canvas);
      });
      $('#slider-contrast').change(function () {
        adjustBrightness(Viewer.$canvas2, Viewer.$canvas);
      });
      $('#slider-brightness').val(brightness);
      $('#slider-contrast').val(contrast);
    };

    /*
     * adjustBrightness($cvssrc,$cvsdst)
     */
    function adjustBrightness($cvssrc, $cvsdst) {
      brightness = parseInt($('#slider-brightness').val());
      contrast = parseInt($('#slider-contrast').val()) / 100;
      popup.updateStatus();

      var multiplier = 1 + Math.min(150, Math.max(-150, brightness)) / 150;
      contrast = Math.max(0, contrast + 1);

      var ctxsrc = $cvssrc[0].getContext('2d');
      var ctxdst = $cvsdst[0].getContext('2d');

      var w = $cvssrc.attr('width');
      var h = $cvssrc.attr('height');

      var dataDesc = ctxsrc.getImageData(0, 0, w, h);
      // left, top, width, height.
      var data = dataDesc.data;

      var p = w * h;
      var pix = p * 4;
      var pix1;
      var pix2;

      var mul;
      var add;
      if (contrast !== 1) {
        mul = multiplier * contrast;
        add = -contrast * 128 + 128;
      }
      else {
        // This if-then is not necessary anymore, is it?
        mul = multiplier;
        add = 0;
      }
      var r;
      var g;
      var b;
      while (p--) {
        if ((r = data[pix -= 4] * mul + add) > 255) {
          data[pix] = 255;
        }
        else {
          if (r < 0) {
            data[pix] = 0;
          }
          else {
            data[pix] = r;
          }
        }

        if ((g = data[pix1 = pix + 1] * mul + add) > 255) {
          data[pix1] = 255;
        }
        else {
          if (g < 0) {
            data[pix1] = 0;
          }
          else {
            data[pix1] = g;
          }
        }

        if ((b = data[pix2 = pix + 2] * mul + add) > 255) {
          data[pix2] = 255;
        }
        else {
          if (b < 0) {
            data[pix2] = 0;
          }
          else {
            data[pix2] = b;
          }
        }
      }
      ctxdst.putImageData(dataDesc, 0, 0);
    }

    popup.dialogReset = function dialogReset() {
    };

    popup.dialogUpdate = function dialogUpdate() {
      // Update dialog with new information.
    };

    popup.updateStatus = function updateStatus() {
      Popups.status.dialogUpdate({
        brightness: brightness,
        contrast: contrast
      });
    };

    // Dialog buttons are defined last to ensure methods are defined.
    popup.spec['buttons'] = {
      Apply: popup.dialogApply,
      Reset: popup.dialogReset,
      Cancel: popup.dialogClose
    };
    return popup;
  };
})(jQuery);
