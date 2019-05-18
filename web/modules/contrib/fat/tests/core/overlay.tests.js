/*jshint strict:true, browser:true, curly:true, eqeqeq:true, expr:true, forin:true, latedef:true, newcap:true, noarg:true, trailing: true, undef:true, unused:true */
/*global Drupal: true, jQuery: true, QUnit:true*/
(function ($, Drupal, window, document, undefined) {
  "use strict";
  /**
   *
   */
  Drupal.tests.overlay = {
    getInfo: function() {
      return {
        name: '',
        description: '',
        group: ''
      };
    },
    setup: function () {
      // Open the overlay.
      window.location.replace('#overlay=admin');
    },
    teardown: function () {},
    tests: {
      overlayShow: function ($, Drupal, window, document, undefined) {
        return function() {
          QUnit.expect(2);
          // Test if overlay is shown.
          QUnit.ok($('#overlay-container'), Drupal.t('Overlay is shown.'));

          // Test if overlay is hidded when X is clicked.
          QUnit.stop();
          setTimeout(function() {
            $('#overlay-container iframe').contents().find('a#overlay-close span').trigger('click');
            QUnit.start();
          }, 800);
          QUnit.ok($('#overlay-container').length === 0, Drupal.t('Overlay is hidden when X is clicked.'));
        };
      }
    }
  };
}(jQuery, Drupal, this, this.document));
