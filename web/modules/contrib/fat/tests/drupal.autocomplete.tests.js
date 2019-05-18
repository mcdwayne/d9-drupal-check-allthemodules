/*jshint strict:true, browser:true, curly:true, eqeqeq:true, expr:true, forin:true, latedef:true, newcap:true, noarg:true, trailing: true, undef:true, unused:true */
/*global Drupal: true, jQuery: true, QUnit:true*/
(function ($, Drupal, window, document, undefined) {
  "use strict";
  /**
   * Tests autocomplete.
   */
  Drupal.tests.autocomplete = {
    getInfo: function() {
      return {
        name: 'autocomplete',
        description: 'Tests for autocomplete.',
        group: 'System',
        useSimulate: true
      };
    },
    tests: {
      existing_item: function ($, Drupal, window, document, undefined) {
        return function() {
          QUnit.expect(6);
          var delay = 1000;

          $('#edit-text1').val('aa').trigger('keyup');
          QUnit.stop();
          setTimeout(function() {
            QUnit.ok($('#autocomplete:visible'), 'autocomplete list visible');
            QUnit.ok($('#autocomplete li').first().find('aaa'), 'aaa found');
            QUnit.ok($('#autocomplete li').first().next().find('aaabbb'), 'aaabbb found');
            QUnit.ok($('#autocomplete li').first().find('ccc').length === 0, 'ccc not found');

            $("#edit-text1").simulate("keydown", { keyCode: 40 });
            $("#edit-text1").simulate("keyup", { keyCode: 13 });

            setTimeout(function() {
              QUnit.ok($('#edit-text1').val() === 'aaa', 'aaa selected');
              QUnit.ok($('#autocomplete').length === 0, 'autocomplete not visible');
              QUnit.start();
            }, delay);
          }, delay);
        };
      },
      non_existing_item: function ($, Drupal, window, document, undefined) {
        return function() {
          QUnit.expect(1);
          var delay = 1000;

          $('#edit-text1').val('xx').trigger('keyup');
          QUnit.stop();
          setTimeout(function() {
            QUnit.ok($('#autocomplete.length').length === 0, 'autocomplete not visible');
            QUnit.start();
          }, delay);
        };
      }
    }
  };
})(jQuery, Drupal, this, this.document);
