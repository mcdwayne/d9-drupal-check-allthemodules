(function ($, Drupal) {

  "use strict";

  /**
   * Filters the block list by a text input search string.
   *
   * Text search input: input.block-filter-text
   * Target element:    input.block-filter-text[data-element]
   * Source text:       .block-filter-text-source
   */
  Drupal.behaviors.socialStatsDatepicker = {
    attach: function (context, settings) {
      $(document).ready(function(){
        $('.pickadate').datepicker({
          dateFormat: 'mm/dd/yy',
          autoSize: true,
          inline: true
        });
      });
    }
  };
}(jQuery, Drupal));
