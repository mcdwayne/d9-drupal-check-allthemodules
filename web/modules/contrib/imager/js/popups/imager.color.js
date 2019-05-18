/**
 * @file
 * Declare Imager module Color dialog - Drupal.imager.popups.colorC.
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
   * Define the color dialog - Hue/Saturation/Lightness.
   *
   * @param {object} spec
   *   Specifications for opening dialog, can also have ad-hoc properties
   *   not used by jQuery dialog but needed for other purposes.
   *
   * @return {dialog}
   *   Return the color dialog.
   */
  Drupal.imager.popups.colorC = function colorC(spec) {
    var Popups = Drupal.imager.popups;
    var Viewer = Drupal.imager.viewer;
    var hue = 0;
    var saturation = 0;
    var lightness = 0;
    var popup;

    var dspec = $.extend({
      name: 'Color',
      title: 'Hue/Saturation/Lightness',
      zIndex: 1015,
      cssId: 'imager-color',
      draggable: true,
      resizable: false,
      position: {
        left: '50px',
        top: '150px'
      }
    }, spec);
    // Initialize the popup.
    popup = Popups.baseC(dspec);

    /**
     * Initialize the slider settings to 0
     */
    function init() {
      hue = 0;
      saturation = 0;
      lightness = 0;
      popup.updateStatus();
    }

    /**
     * When a user clicks the apply, reset or cancel button
     * @param buttonName
     */
    popup.onButtonClick = function onButtonClick(buttonName) {
      switch (buttonName) {
        case 'imager-color-apply':
          Viewer.applyFilter(adjustColor);
          break;

        case 'imager-color-reset':
          init();
          $('#slider-hue').val(0);
          $('#slider-saturation').val(0);
          $('#slider-lightness').val(0);
          adjustColor(Viewer.$canvas2, Viewer.$canvas);
          break;

        case 'imager-color-cancel':
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

    popup.dialogOnClose = function dialogOnClose() {
    };

    popup.dialogInit = function dialogInit() {
      init();
      if (popup.dialogIsOpen()) {
        $('#slider-hue').change(function () {
          adjustColor(Viewer.$canvas2, Viewer.$canvas);
        });
        $('#slider-saturation').change(function () {
          adjustColor(Viewer.$canvas2, Viewer.$canvas);
        });
        $('#slider-lightness').change(function () {
          adjustColor(Viewer.$canvas2, Viewer.$canvas);
        });
        $('#slider-hue').val(hue);
        $('#slider-saturation').val(saturation);
        $('#slider-lightness').val(0);
      }
    };

    function adjustColor($cvssrc, $cvsdst) {
      Popups.busy.show();
      hue = parseInt($('#slider-hue').val() * 100) / 100;
      saturation = parseInt($('#slider-saturation').val() * 100) / 9000;
      lightness = parseInt($('#slider-lightness').val() * 100) / 10000;
      popup.updateStatus();

      var w = $cvssrc.attr('width');
      var h = $cvssrc.attr('height');

      var ctxsrc = $cvssrc[0].getContext('2d');
      var ctxdst = $cvsdst[0].getContext('2d');

      var dataDesc = ctxsrc.getImageData(0, 0, w, h);
      // left, top, width, height.
      var data = dataDesc.data;

      // This seems to give the same result as Photoshop.
      var satMul;
      if (saturation < 0) {
        satMul = 1 + saturation;
      }
      else {
        satMul = 1 + saturation * 2;
      }

      hue = (hue % 360) / 360;
      var hue6 = hue * 6;

      var light255 = lightness * 255;
      var lightp1 = 1 + lightness;
      var lightm1 = 1 - lightness;

      var p = w * h;
      var pix = p * 4;
      var pix1 = pix + 1;
      var pix2 = pix + 2;

      while (p--) {

        var r = data[pix -= 4];
        var g = data[pix1 = pix + 1];
        var b = data[pix2 = pix + 2];

        var h_;
        var s;
        var v;

        if (hue !== 0 || saturation !== 0) {
          // ok, here comes rgb to hsl + adjust + hsl to rgb, all in one jumbled mess.
          // It's not so pretty, but it's been optimized to get somewhat decent performance.
          // The transforms were originally adapted from the ones found in Graphics Gems, but have been heavily modified.
          var vs = r;
          if (g > vs) {
            vs = g;
          }
          if (b > vs) {
            vs = b;
          }
          var ms = r;
          if (g < ms) {
            ms = g;
          }
          if (b < ms) {
            ms = b;
          }
          var vm = (vs - ms);
          var l = (ms + vs) / 510;
          if (l > 0) {
            if (vm > 0) {
              if (l <= 0.5) {
                s = vm / (vs + ms) * satMul;
                if (s > 1) {
                  s = 1;
                }
                v = (l * (1 + s));
              }
              else {
                s = vm / (510 - vs - ms) * satMul;
                if (s > 1) {
                  s = 1;
                }
                v = (l + s - l * s);
              }
              if (r === vs) {
                if (g === ms) {
                  h_ = 5 + ((vs - b) / vm) + hue6;
                }
                else {
                  h_ = 1 - ((vs - g) / vm) + hue6;
                }
              }
              else {
                if (g === vs) {
                  if (b === ms) {
                    h_ = 1 + ((vs - r) / vm) + hue6;
                  }
                  else {
                    h_ = 3 - ((vs - b) / vm) + hue6;
                  }
                }
                else {
                  if (r === ms) {
                    h_ = 3 + ((vs - g) / vm) + hue6;
                  }
                  else {
                    h_ = 5 - ((vs - r) / vm) + hue6;
                  }
                }
              }
              if (h_ < 0) {
                h_ += 6;
              }
              if (h_ >= 6) {
                h_ -= 6;
              }
              var m = (l + l - v);
              var sextant = h_ >> 0;
              if (sextant === 0) {
                r = v * 255;
                g = (m + ((v - m) * (h_ - sextant))) * 255;
                b = m * 255;
              }
              else if (sextant === 1) {
                r = (v - ((v - m) * (h_ - sextant))) * 255;
                g = v * 255;
                b = m * 255;
              }
              else if (sextant === 2) {
                r = m * 255;
                g = v * 255;
                b = (m + ((v - m) * (h_ - sextant))) * 255;
              }
              else if (sextant === 3) {
                r = m * 255;
                g = (v - ((v - m) * (h_ - sextant))) * 255;
                b = v * 255;
              }
              else if (sextant === 4) {
                r = (m + ((v - m) * (h_ - sextant))) * 255;
                g = m * 255;
                b = v * 255;
              }
              else if (sextant === 5) {
                r = v * 255;
                g = m * 255;
                b = (v - ((v - m) * (h_ - sextant))) * 255;
              }
            }
          }
        }

        if (lightness < 0) {
          r *= lightp1;
          g *= lightp1;
          b *= lightp1;
        }
        else {
          if (lightness > 0) {
            r = r * lightm1 + light255;
            g = g * lightm1 + light255;
            b = b * lightm1 + light255;
          }
        }

        if (r < 0) {
          data[pix] = 0;
        }
        else {
          if (r > 255) {
            data[pix] = 255;
          }
          else {
            data[pix] = r;
          }
        }

        if (g < 0) {
          data[pix1] = 0;
        }
        else {
          if (g > 255) {
            data[pix1] = 255;
          }
          else {
            data[pix1] = g;
          }
        }

        if (b < 0) {
          data[pix2] = 0;
        }
        else {
          if (b > 255) {
            data[pix2] = 255;
          }
          else {
            data[pix2] = b;
          }
        }

      }
      ctxdst.putImageData(dataDesc, 0, 0);
      Popups.busy.hide();
    }

    popup.dialogUpdate = function dialogUpdate() {
    };

    popup.updateStatus = function updateStatus() {
      Popups.status.dialogUpdate({
        hue: parseInt(hue * 100) / 100,
        saturation: parseInt(saturation * 100) / 100,
        lightness: parseInt(lightness * 100) / 100
      });
    };

    return popup;
  };
})(jQuery);
