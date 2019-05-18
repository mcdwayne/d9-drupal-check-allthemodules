/*jshint strict:true, browser:true, curly:true, eqeqeq:true, expr:true, forin:true, latedef:true, newcap:true, noarg:true, trailing: true, undef:true, unused:true */
/*global Drupal: true, jQuery: true, QUnit:true*/
(function ($, Drupal, window, document, undefined) {
  "use strict";
  /**
   * Tests i1473314.
   * @TODO: Only works in a frameset
   */
  Drupal.tests.i1473314 = {
    getInfo: function() {
      return {
        name: 'i1473314',
        description: 'Tests for i1473314.',
        group: 'System'
      };
    },
    tests: {
      base_unique: function ($, Drupal, window, document, undefined) {
        return function() {
          QUnit.expect(3);
          var delay = 1000;

          QUnit.ok($('.base_unique').length === 0, 'No responses found for base_unique');
          $('#base_unique').trigger('click');
          QUnit.stop();
          setTimeout(function() {
            QUnit.ok($('.base_unique').length === 1, '1 Response found for base_unique');
            $('#base_unique').trigger('click');
            setTimeout(function() {
              QUnit.ok($('.base_unique').length === 2, '2 Responses found for base_unique');
              QUnit.start();
            }, delay);
          }, delay);
        };
      },
      base_multiple: function ($, Drupal, window, document, undefined) {
        return function() {
          QUnit.expect(3);
          var delay = 1000;

          QUnit.ok($('.base_multiple').length === 0, 'No responses found for base_multiple');
          $('#base_multiple').trigger('click');
          QUnit.stop();
          setTimeout(function() {
            QUnit.ok($('.base_multiple').length === 1, '1 Response found for base_multiple');
            $('#base_multiple').trigger('click');
            setTimeout(function() {
              QUnit.ok($('.base_multiple').length === 2, '2 Responses found for base_multiple');
              QUnit.start();
            }, delay);
          }, delay);
        };
      },
      selector_unique: function ($, Drupal, window, document, undefined) {
        return function() {
          QUnit.expect(3);
          var delay = 1000;

          QUnit.ok($('.selector_unique').length === 0, 'No responses found for selector-unique');
          $('.selector-unique').trigger('click');
          QUnit.stop();
          setTimeout(function() {
            QUnit.ok($('.selector_unique').length === 1, '1 Response found for selector-unique');
            $('.selector-unique').trigger('click');
            setTimeout(function() {
              QUnit.ok($('.selector_unique').length === 2, '2 Responses found for selector-unique');
              QUnit.start();
            }, delay);
          }, delay);
        };
      }
    }
  };
})(jQuery, Drupal, this, this.document);
