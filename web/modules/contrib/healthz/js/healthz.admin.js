/**
 * @file
 * Attaches administration-specific behavior for the Healthz module.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Displays and updates the status of healthz checks on the admin page.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches behaviors to the healthz admin settings page.
   */
  Drupal.behaviors.healthzStatus = {
    attach: function (context, settings) {
      var $context = $(context);
      $context.find('#checks-status-wrapper input.form-checkbox').once('check-status').each(function () {
        var $checkbox = $(this);
        // Retrieve the tabledrag row belonging to this check.
        var $row = $context.find('#' + $checkbox.attr('id').replace(/-status$/, '-weight')).closest('tr');
        // Retrieve the vertical tab belonging to this check.
        var $checkSettings = $context.find('#' + $checkbox.attr('id').replace(/-status$/, '-settings'));
        var $checkSettingsTab = $checkSettings.data('verticalTab');

        // Bind click handler to this checkbox to conditionally show and hide
        // the check's tableDrag row and vertical tab pane.
        $checkbox.on('click.checkUpdate', function () {
          if ($checkbox.is(':checked')) {
            $row.show();
            if ($checkSettingsTab) {
              $checkSettingsTab.tabShow().updateSummary();
            }
            else {
              // On very narrow viewports, Vertical Tabs are disabled.
              $checkSettings.show();
            }
          }
          else {
            $row.hide();
            if ($checkSettingsTab) {
              $checkSettingsTab.tabHide().updateSummary();
            }
            else {
              // On very narrow viewports, Vertical Tabs are disabled.
              $checkSettings.hide();
            }
          }
          // Restripe table after toggling visibility of table row.
          Drupal.tableDrag['check-order'].restripeTable();
        });

        // Attach summary for configurable filters (only for screen readers).
        if ($checkSettingsTab) {
          $checkSettingsTab.details.drupalSetSummary(function (tabContext) {
            return $checkbox.is(':checked') ? Drupal.t('Enabled') : Drupal.t('Disabled');
          });
        }

        // Trigger our bound click handler to update elements to initial state.
        $checkbox.triggerHandler('click.checkUpdate');
      });
    }
  };

})(jQuery, Drupal);
