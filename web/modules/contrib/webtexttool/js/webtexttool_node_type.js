/**
 * @file
 * Custom node type JS for the webtexttool module.
 */

(function ($) {
  'use strict';
  Drupal.behaviors.webtexttool_vertical_node_type = {
    attach: function (context) {
      // Provide the summary for the node type form.
      $('fieldset.webtexttool-node-type-settings-form', context).drupalSetSummary(function (context) {
        var vals = [];
        var webtexttool_enabled = $('.form-item-webtexttool-enabled input:checked', context).next('label').text();
        if (webtexttool_enabled) {
          vals.push(webtexttool_enabled);
        }
        return Drupal.checkPlain(vals.join(' '));
      });
    }
  };

})(jQuery);
