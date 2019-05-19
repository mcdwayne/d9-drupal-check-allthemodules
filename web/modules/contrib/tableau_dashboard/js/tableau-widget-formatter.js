/**
 * @file
 * Function which gets relevant data out of URL if user pastes whole URL to Tableau dashbaord into Tableau Drupal field.
 *
 * User: rok
 * Date: 15/05/2017
 * Time: 09:24.
 */

(function ($) {

  'use strict';
  Drupal.behaviors.tableau_dashboard_widget = {
    attach: function (context, settings) {
      $(".field--widget-tableau-dashboard-widget input").on("paste", function (e) {
        var $input = $(this);
        setTimeout(function () {
          var currentVal = e.target.value;
          var regex = new RegExp("[http|https]:\/\/.*\/views\/(.*?\/.*?)$", "g"),
            results = regex.exec(currentVal);
          if (results && results[1] !== undefined) {
            $input.val(results[1]);
          }
        }, 500);
      });
    }
  };

})(jQuery, drupalSettings);
