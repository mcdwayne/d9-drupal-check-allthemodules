/**
 * @file
 * Drupal behavior for Skillset Inview -- assist color selection preview.
 */

(function ($, Drupal) {
  'use strict';

  /**
   * Attach behavior for the Overview form.
   */
  Drupal.behaviors.skillsetInviewColor = {
    attach: function (context) {

      // Init setup. SKILLBAR PREVIEW with current colors.
      $('.skill-line', context).once('skillbarColorInit').each(function (index, value) {
        // With active JS..remove attr so that js can now set colors. degrade state page load.
        $('.skill-line, .skill-bar, .skill-label, .percent.inside, .percent.outside', context).removeAttr('style');
        Drupal.skillsetInviewColor.updatePreview();
      });

      // Add farbtastic to page and basic connect to inputs.
      var farb = $.farbtastic('.farbtastic-element');
      $('.color-helper input', context).once('colorHelperInit').each(function (index, value) {
        farb.linkTo(this);
      }).focus(function () {
        farb.linkTo(this);
      });

      // Focus on first input.
      $('input[name="color-bar"]', context).focus();

      // Block input return submits.
      $('input[type="text"], input[type="range"]', context).on('keyup keypress', function (e) {
        if (e.keyCode === 13) {
          e.preventDefault();
          return false;
        }
      });

      // Watch FRAB by basic mouse events, and update preview.
      $('.farbtastic-element', context).once('skillsetFrabWatch').on('mouseout mouseup', function () {
        Drupal.skillsetInviewColor.updatePreview();
      });

      // Update preview when inputs changed by manual typing.
      $('.color-helper input', context).once('skillsetColorAssist').on('input paste change', function () {
        Drupal.skillsetInviewColor.updatePreview();
      });

      // Change percent visual description when changed, and update preview.
      $('.form-type-range input', context).once('skillsetOpacityChange').on('input change', function () {
        var name = $(this).attr('name');
        var value = $(this).val();

        switch (name) {
          case 'color-bar-opacity':
            var $currentBar = $('input[name="color-bar"]').val();
            var rgbaBar = Drupal.skillsetInviewColor.hexToRgb($currentBar, value);
            $('.skill-bar').css({background: rgbaBar});
            break;

          case 'color-back-opacity':
            var $currentBack = $('input[name="color-back"]').val();
            var rgbaBack = Drupal.skillsetInviewColor.hexToRgb($currentBack, value);
            $('.skill-line').css({background: rgbaBack});
            break;

        }
      });

    }
  };

  /**
   * Init namespace.
   */
  Drupal.skillsetInviewColor = Drupal.skillsetInviewColor || {};

  /**
   * Function:  hexToRgb converter.
   *
   * @param {string} hex
   * hexadecimal color to process
   * @param {number} opacity
   * opacity range (0-100) to process
   *
   * @return {string}
   * rbga color value to use for CSS
   */
  Drupal.skillsetInviewColor.hexToRgb = function (hex, opacity) {
    var color = hex.replace('#', '');
    color = color.match(new RegExp('(.{' + (color.length) / 3 + '})', 'g'));
    for (var i = 0; i < color.length; i++) {
      color[i] = parseInt((color[i].length === 1 ? color[i] + color[i] : color[i]), 16);
    }
    if (typeof opacity != 'undefined') {
      var percent = parseFloat(opacity / 100).toFixed(2);
      color.push(percent);
    }
    return 'rgba(' + color.join(',') + ')';
  };

  /**
   * Function:  validates a input as a hex color.
   *
   * @param {string} input
   * String to test.
   *
   * @return {bool}
   * Input is valid, or not.
   */
  Drupal.skillsetInviewColor.validateHex = function (input) {
    var regColor = /^(#)?([0-9a-fA-F]{3})([0-9a-fA-F]{3})?$/;
    if (regColor.test(input) === false) {
      return false;
    }
    return true;
  };

  /**
   * Function:  task to update preview.
   */
  Drupal.skillsetInviewColor.updatePreview = function () {
    var colorBar = $('input[name="color-bar"]').val();
    var colorBack = $('input[name="color-back"]').val();
    var colorBorder = $('input[name="color-border"]').val();
    var colorLabels = $('input[name="color-labels"]').val();
    var colorPercentInside = $('input[name="color-percent-inside"]').val();
    var colorPercentOutside = $('input[name="color-percent-outside"]').val();

    if (Drupal.skillsetInviewColor.validateHex(colorBar) &&
      Drupal.skillsetInviewColor.validateHex(colorBack) &&
      Drupal.skillsetInviewColor.validateHex(colorBorder) &&
      Drupal.skillsetInviewColor.validateHex(colorLabels) &&
      Drupal.skillsetInviewColor.validateHex(colorPercentInside) &&
      Drupal.skillsetInviewColor.validateHex(colorPercentOutside)
    ) {
      var colorBarOpacity = $('input[name="color-bar-opacity"]').val();
      var colorBackOpacity = $('input[name="color-back-opacity"]').val();
      var barColor = Drupal.skillsetInviewColor.hexToRgb(colorBar, colorBarOpacity);
      var barBack = Drupal.skillsetInviewColor.hexToRgb(colorBack, colorBackOpacity);
      $('.skill-bar').css({background: barColor});
      $('.skill-line').css({background: barBack, border: '1px solid ' + colorBorder});
      $('.skill-label').css({color: colorLabels});
      $('.percent.inside').css({color: colorPercentInside});
      $('.percent.outside').css({color: colorPercentOutside});
    }
  };

}(jQuery, Drupal));
