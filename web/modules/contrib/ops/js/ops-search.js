/**
 * @file
 * File to get text to be search on page and highlight the text.
 */

(function ($) {
  'use strict';
  Drupal.behaviors.ops = {
    attach: function (context, settings) {
      $('#ops-text-search').keyup(function () {
        // Pull in the new value.
        var searchTerm = $(this).val();
        // remove any old highlighted terms
        $('body').remove_highlight();
        // Disable highlighting if empty.
        if (searchTerm) {
          // Highlight the new term.
          $('body').highlight(searchTerm);
          $('.ops-highlight').css('background-color', drupalSettings.ops.onpagesearch.ops_bk_color);
          $('.ops-highlight').css('color', drupalSettings.ops.onpagesearch.ops_text_color);
        }
      });
    }
  };
})(jQuery);
