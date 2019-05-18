(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.privateContentSettingsSummary = {
    attach: function (context) {
      var $context = $(context);
      $context.find('#edit-private-content').drupalSetSummary(function (context) {
        var vals = [];

        // Inclusion select field.
        $(context).find('#edit-private input:checked').each(function() {
          vals.push($("label[for='" + $(this).attr("id") + "']").text());
        });

        return vals.join(', ');
      });
    }
  };

})(jQuery, Drupal);
