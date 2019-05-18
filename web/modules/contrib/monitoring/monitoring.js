/**
 * @file
 * Javascript behaviors for the monitoring module.
 */

(function($) {

  /**
   * Behavior that adds and controls the toggle link on the overview page.
   */
  Drupal.behaviors.monitoringOverviewToggle = {
    attach: function(context) {
      // Check if there are any criticals, warnings or unknowns.
      if (drupalSettings.monitoring_escalated_sensors > 0) {
          $overview = $(context).find('.monitoring-overview-summary').once('monitoring-button');
          if ($overview.length) {
              // Inject toggle link into DOM.
              $('<a class="button button--primary button--small" href="#">' + Drupal.t('Show OK sensors') + '</a>')
                  .appendTo($overview)
                  .click(
                      function () {
                          if ($(this).text() == Drupal.t('Show OK sensors')) {
                              $(this).text(Drupal.t('Hide OK sensors'));
                              $('#monitoring-sensors-overview tr.monitoring-ok, #monitoring-sensors-overview tr.sensor-category-ok', context).fadeIn();
                          }
                          else {
                              $(this).text(Drupal.t('Show OK sensors'));
                              $('#monitoring-sensors-overview tr.monitoring-ok, #monitoring-sensors-overview tr.sensor-category-ok', context).fadeOut();
                          }
                      }
                  );
              // Hide OK sensors by default.
              $('#monitoring-sensors-overview tr.monitoring-ok, #monitoring-sensors-overview tr.sensor-category-ok', context).hide();
          };
      }
    }
  }
})(jQuery);

