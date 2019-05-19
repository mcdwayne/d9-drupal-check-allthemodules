/**
 * @file
 * Custom vertical tab JS for the webtexttool module.
 */

(function ($) {
  'use strict';
  Drupal.behaviors.webtexttool_vertical = {
    attach: function (context) {
      // Provide summary when editting a node.
      $('fieldset.webtexttool-form', context).drupalSetSummary(function (context) {
        var vals = [];
        vals.push($('#edit-webtexttool-keywords').val());
        vals.push($('#edit-webtexttool-language').find(':selected').text());
        return vals.join('<br />');
      });
    }
  };

})(jQuery);
