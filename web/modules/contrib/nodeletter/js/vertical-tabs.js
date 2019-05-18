(function ($) {

  /**
   * Update the summary for the nodeletter's vertical tab.
   */
  Drupal.behaviors.nodeletterFieldsetSummary = {
    attach: function (context) {
      $('details#edit-nodeletter', context).drupalSetSummary(function (context) {
        if ($('#edit-nodeletter-enabled', context).prop('checked')) {
          return Drupal.t('Enabled');
        }
        else {
          return Drupal.t('Disabled');
        }
      });
    }
  };

})(jQuery);
