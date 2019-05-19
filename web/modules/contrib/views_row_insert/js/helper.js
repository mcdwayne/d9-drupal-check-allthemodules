/**
 * Helper JS file for the settings form.
 */
(function ($) {

  'use strict';

  Drupal.behaviors.views_row_insert = {
    attach: function (context, settings) {
      var rowData = $('.form-item-style-options-custom-row-data');
      var blockName = $('.form-item-style-options-block-name');
      var limitField = $('.form-item-style-options-row-limit');

      /**
       *  Shows/hides form element.
       *  @param type string
       *  a string containing 'block' or 'text' value
       *  @param mode string
       *  a string argument for show()/hide() jQuery functions.
       */
      function showElement(type, mode) {
        if (type === 'block') {
          rowData.hide(mode);
          blockName.show(mode);
        }
        else {
          rowData.show(mode);
          blockName.hide(mode);
        }
      }

      $('input:radio[name="style_options[data_mode]"]').change(
        function () {
          if ($(this).is(':checked') && $(this).val() === 'vri_block') {
            showElement('block', 'slow');
          }
          else {
            showElement('text', 'slow');
          }
        }
      );
      if ($('input.vri_block').is(':checked')) {
        showElement('block', '');
      }
      else {
        showElement('text', '');
      }
      if ($('input[name="style_options[row_limit_flag]"]').prop('checked')) {
        limitField.show();
      }
      else {
        limitField.hide();
      }
      $('input[name="style_options[row_limit_flag]"]').change(
        function () {
          if ($(this).is(':checked')) {
            limitField.show('slow');
          }
          else {
            limitField.hide('slow');
          }
        }
      );
    }
  };
}(jQuery));
