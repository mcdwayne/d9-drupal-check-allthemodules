/*jshint strict:true, browser:true, curly:true, eqeqeq:true, expr:true, forin:true, latedef:true, newcap:true, noarg:true, trailing: true, undef:true, unused:true */
/*global Drupal: true, jQuery: true, QUnit:true*/
(function ($, Drupal, window, document, undefined) {
  "use strict";
  /**
   *
   */
  Drupal.tests.password = {
    getInfo: function() {
      return {
        name: 'Password indicators test',
        description: 'Testing the password indicators when creating users.',
        group: 'Core',
      };
    },
    tests: {
      passwordIndicators: function ($, Drupal, window, document, undefined) {
        return function() {
          QUnit.expect(3);

          // Set a strong password.
          $('#edit-pass-pass1').val('Drup@7').trigger('input');

          // Check if the password strength text changes to 'Strong'.
          QUnit.ok($('.password-strength-text').text() === 'Strong', Drupal.t('The password strength text changes to "Strong".'));

          // Set the confirm password.
          $('#edit-pass-pass2').val('Drup@7').trigger('input');

          // Check if the password confirm text changes to "Passwords match: yes".
          QUnit.ok($('.password-confirm').text() === "Passwords match: yes", Drupal.t('The password confirm text changes to "Passwords match: yes".'));

          // Check if the password siggestions are hidden.
          QUnit.ok($('.password-suggestions').css('display') === 'none', Drupal.t('The password suggestions are hidden.'));
        };
      }
    }
  };
}(jQuery, Drupal, this, this.document));
