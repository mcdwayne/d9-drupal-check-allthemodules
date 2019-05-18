/**
 * @file scheduling.js
 *
 * Adds some usability tweaks to lists of scheduling items.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Registers behaviours related to view widget.
   */
  Drupal.behaviors.schedulingMode = {
    attach: function (context) {

      $(context)
        .find('.field--type-scheduling-value')
        .once('scheduling-mode')
        .each(function (index, element) {
          var field = $(element).prev('.field--type-scheduling-mode');
          if (field[0]) {
            var select = $(field[0]).find('select')[0];
            $(select).change(function (event) {
              $(element).attr('data-mode', $(event.target).val());
            });
            $(select).change();
          }

        });
    }
  };

  Drupal.behaviors.schedulingItem = {
    attach: function (context) {

      $(context)
        .find('.field--type-scheduling-value .field-multiple-table tr')
        .each(function (index, element) {
          var row = $(element).find('td:nth-of-type(2) > div.row').length > 0 ? $(element).find('td:nth-of-type(2) > div.row') : $(element).find('td:nth-of-type(2) > .ajax-new-content > div.row');
          if ($(row).hasClass('range')) {
            $(row).closest('tr').once('scheduling-item').attr('data-mode', 'range');
          }
          if ($(row).hasClass('recurring')) {
            $(row).closest('tr').once('scheduling-item').attr('data-mode', 'recurring');
          }
        });

    }
  };

}(jQuery, Drupal, drupalSettings));
