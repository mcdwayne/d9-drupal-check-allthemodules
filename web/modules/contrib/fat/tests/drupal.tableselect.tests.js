/*jshint strict:true, browser:true, curly:true, eqeqeq:true, expr:true, forin:true, latedef:true, newcap:true, noarg:true, trailing: true, undef:true, unused:true */
/*global Drupal: true, jQuery: true, QUnit:true*/
(function ($, Drupal, window, document, undefined) {
  "use strict";
  /**
   * TableSelect.
   */
  Drupal.tests.testswarm_forms_tableselect = {
    getInfo: function() {
      return {
        name: 'Tableselect',
        description: 'Tests for Tableselect.',
        group: 'System'
      };
    },
    tests: {
      select_all: function ($, Drupal, window, document, undefined) {
        return function() {
          QUnit.expect(2);

          var $check_all = $('table.table-select-processed .select-all input:visible');
          // Machine name should be hidden on page load
          QUnit.ok($check_all.length, Drupal.t('"Select all" checkbox found'));

          // Check the select-all checkbox
          $check_all.trigger('click');
          var all_checked = true;
          $('table.table-select-processed tbody tr').each(function() {
            if (!$(this).hasClass('selected')) {
              all_checked = false;
            }
          });
          QUnit.ok(all_checked, Drupal.t('All checkboxes in the table are checked.'));
        };
      },
      deselect_all: function ($, Drupal, window, document, undefined) {
        return function() {
          QUnit.expect(1);

          // Uncheck the select-all checkbox
          $('table.table-select-processed .select-all input').trigger('click');
          var all_checked = false;
          $('table.table-select-processed tr input[type=checkbox]:visible').each(function() {
            if ($(this).attr('checked') || $(this).attr('checked') === 'checked') {
              all_checked = false;
            }
          });
          QUnit.ok(!all_checked, Drupal.t('No checkboxes in the table are checked'));
        };
      }
    }
  };
})(jQuery, Drupal, this, this.document);
