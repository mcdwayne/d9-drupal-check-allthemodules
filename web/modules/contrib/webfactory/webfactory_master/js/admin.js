/**
 * @file
 * Contains the definition of the behavior webfactory_master_admin.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.webfactory_master_admin = {
    attach: function (context, settings) {
      $('.sat-status-deploy', context).each(function (i) {
        var satId = $(this).attr('data-satId');
        var deploySpinner = new Drupal.ProgressDeploy(satId);
        $(this).html(deploySpinner.element);
        // Let 1 min before checking again.
        setTimeout(function () {
          deploySpinner.startMonitoring('/admin/config/services/webfactory/satellite_entity/' + satId + '/check_deploy', 10000);
        }, 60000);
      });
    }
  };
})(jQuery, Drupal, drupalSettings);
