(function ($, Drupal) {

  "use strict";

  /**
   * Attach behaviors for the display settings.
   */
  Drupal.behaviors.entityReferenceFormatterStatus = {
    attach: function (context) {
      $('.entity-reference-formatter-status-wrapper input.form-radio', context).once('entity-reference-formatter-status').each(function () {
        var $radio = $(this);
        var formatterSelector = $radio.attr('data-drupal-selector').replace(/-status$/, '');

        var tab = $('[data-drupal-selector="' + formatterSelector + '-settings"]', context).data('verticalTab');
        // Because the vertical tabs aren't currently working, just work on the
        // fieldset itself for now.
        var $fieldset = $('[data-drupal-selector="' + formatterSelector + '-settings"]', context);

        // Bind click handler to this checkbox to conditionally show and hide the
        // filter's tableDrag row and vertical tab pane.
        $radio.bind('click.filterUpdate', function () {
          if ($radio.is(':checked')) {
            $fieldset.show();
          }
          else {
            $fieldset.hide();
          }
        });

        // Attach summary for configurable filters (only for screen-readers).
        if (tab) {
          tab.fieldset.drupalSetSummary(function (tabContext) {
            return $radio.is(':checked') ? Drupal.t('Enabled') : Drupal.t('Disabled');
          });
        }

        // Trigger our bound click handler to update elements to initial state.
        $radio.triggerHandler('click.filterUpdate');
      });
    }
  };

})(jQuery, Drupal);
