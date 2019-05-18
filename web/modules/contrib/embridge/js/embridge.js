/**
 * @file
 * AJAX commands used by embridge module.
 */

(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.embridge_search_submit = {
    attach: function (context, settings) {
      $('.embridge-search-form input[name="filename"]').on('keypress', function (event) {
        if (event.keyCode == 13) {
          $('.embridge-ajax-search-submit').trigger('mousedown');
        }
      });
    }
  };

  Drupal.behaviors.embridge_search_choose_asset = {
    attach: function (context, settings) {
      // Tie click events for choosing an image to clicking the submission button.
      $('.embridge-choose-file').once().on('click', function (event) {
        event.preventDefault();
        // Set the result that was chosen.
        var asset_id = $(this).attr('data-asset-entity-id');
        $('[name="result_chosen"]').val(asset_id);

        $('.embridge-ajax-select-file').trigger('click');
      });
    }
  };

  /**
   * Command to save the contents of an embridge asset search.
   */
  Drupal.AjaxCommands.prototype.embridgeSearchDialogSave = function (ajax, response, status) {
    var data_selector = response.values.drupal_selector;
    var entity_id = response.values.entity_id;
    var upload_button_selector = 'input[data-drupal-selector="' + data_selector + '-upload-button"]';

    // Create inputs as if a file had been added to the form.
    jQuery('input[data-drupal-selector="' + data_selector + '-fids"]').val(entity_id);
    jQuery('input[data-drupal-selector="' + data_selector + '-display"]').val("1");

    // Trigger an "upload" of the asset.
    jQuery(upload_button_selector).trigger('mousedown');
  };

  /**
   * Enables bootstrap collapse for 'more options'.
   */
  Drupal.behaviors.embridge_search_ui = {
    attach: function (context, settings) {
      // Hide collapsed sections on load.
      $(".collapse").each(function() {
        $(this).hide();
      });

      // Switch the "Open" and "Close" state per click then slide up/down (depending on open/close state).
      $('a[data-toggle="collapse"]', context).once('embridge').click(function() {
        $(this).next('.collapse').toggleClass("active").slideToggle();
        if ($(this).next('.collapse').filter(".active").length) {
          $(this).text(Drupal.t("Hide search options"));
        }
        else {
          $(this).text(Drupal.t("More search options"));
        }
        return false;
      });
    }
  };

})(jQuery, Drupal);
