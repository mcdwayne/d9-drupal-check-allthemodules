/**
 * @file
 * Settings restricted values on date fieldset.
 */

(function ($) {
  'use strict';
  
  Drupal.behaviors.blockByDateSettings = {
    attach: function () {
      $('[data-drupal-selector="edit-visibility-date-limit"]').drupalSetSummary(function (context) {
        var todate_checkbox = $(context).find('input[name="visibility[date_limit][dates_between][enable_end_date]"]').val();
        var from_date = $(context).find('input[name="visibility[date_limit][dates_between][from_date]"]').val();
        var to_date = $(context).find('input[name="visibility[date_limit][dates_between][to_date]"]').val();
        if (from_date != '' || todate_checkbox != undefined && to_date != '') {
          if (from_date != '' && todate_checkbox == undefined && to_date == '') {
            return Drupal.t('Restricted by From Date.');
          }
          else if (from_date != '' && todate_checkbox == undefined && to_date != '') {
            return Drupal.t('Restricted by From Date.');
          }
          else if (from_date != '' && todate_checkbox == 1 && to_date == '') {
            return Drupal.t('Restricted by From Date.');
          }
          else if ((from_date != '' && todate_checkbox != undefined && to_date != '') || (from_date == '' && todate_checkbox != undefined && to_date != '')) {
            return Drupal.t('Restricted by certain from and to dates.');
          }
        }
        else {
          return Drupal.t('Not restricted');
        }
      });
    }
  }
})(jQuery);
