/*jshint strict:true, browser:true, curly:true, eqeqeq:true, expr:true, forin:true, latedef:true, newcap:true, noarg:true, trailing: true, undef:true, unused:true */
/*global Drupal: true, jQuery: true, QUnit:true*/
(function ($, Drupal, window, document, undefined) {
  "use strict";
  /**
   * Required radio and AJAX.
   */
  Drupal.tests.forms_radios = {
    getInfo: function() {
      return {
        name: 'Required radio and AJAX',
        description: 'Tests for Required radio and AJAX.',
        group: 'System'
      };
    },
    tests: {
      empty_radio: function ($, Drupal, window, document, undefined) {
        return function() {
          QUnit.expect(1);

          // Make sure the radio buttons aren't checked
          QUnit.equal($('input[name="user_register"]:checked').length, 0, Drupal.t('No radio is checked'));
        };
      },
      empty_radio_message: function ($, Drupal, window, document, undefined) {
        return function() {
          QUnit.expect(1);

          // Check for messages.error
          QUnit.equal($('div.messages.error:visible').length, 0, Drupal.t('"An illegal choice has been detected. Please contact the site administrator." is not displayed'));
        };
      }
    }
  };
})(jQuery, Drupal, this, this.document);
