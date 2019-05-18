(function ($, window) {

  "use strict";

  Drupal.behaviors.perUserBlockSettingsSummary = {
    attach: function () {
      // @todo Investigate if we still need this.
      // The drupalSetSummary method required for this behavior is not available
      // on the Blocks administration page, so we need to make sure this
      // behavior is processed only if drupalSetSummary is defined.
      if (typeof jQuery.fn.drupalSetSummary === 'undefined') {
        return;
      }

      $("#edit-third-party-settings-per-user-block").drupalSetSummary(function (context) {
        return $(context).find('input[type="radio"]:checked + label').text();
      });
    }
  };

})(jQuery, window);
