/*jshint strict:true, browser:true, curly:true, eqeqeq:true, expr:true, forin:true, latedef:true, newcap:true, noarg:true, trailing: true, undef:true, unused:true */
/*global Drupal: true, jQuery: true, QUnit:true*/
(function ($, Drupal, window, document, undefined) {
  "use strict";

  Drupal.tests.announce = {
    getInfo: function() {
      return {
        name: 'Drupal announce',
        description: 'Tests to exercise the Drupal.announce() utility.',
        group: 'System'
      };
    },
    setup: function () {},
    teardown: function () {},
    tests: {
      announcemessage: function () {
        return function() {
          QUnit.expect(3);
          var $live = $('#drupal-live-announce');
          var text = 'From whence the willow tree did spring';

          // Verify that the aria live element is present.
          QUnit.equal($live.length, 1, Drupal.t('The ARIA Live element is present.'));
          // There is a 200ms wait before the text will print.
          // And we have to wait an additional 200ms before calling Drupal.announce
          // as the toolbar's initial orientation "change" might just have fired.
          QUnit.stop();
          setTimeout(function () {
            // Pass a string to announce and verify that it prints to the live region.
            Drupal.announce(Drupal.t(text));
            setTimeout(function () {
              // Verify the text.
              QUnit.equal($live.text(), text, Drupal.t('The ARIA Live element contains the test text.'));
              // Verify the priority is polite.
              QUnit.equal($live.attr('aria-live'), 'polite', Drupal.t('The ARIA Live element priority is polite.'));
              QUnit.start();
            }, 200);
          }, 200);
        };
      },
      announcepriority: function () {
        return function () {
          QUnit.expect(1);

          var $live = $('#drupal-live-announce');
          var text = 'From whence the willow tree did spring';

          // Pass a string to announce and verify that it prints to the live region.
          Drupal.announce(Drupal.t(text), 'assertive');
          // There is a 200ms wait before the text will print.
          QUnit.stop();
          setTimeout(function () {
            // Verify the priority is assertive.
            QUnit.equal($live.attr('aria-live'), 'assertive', Drupal.t('The ARIA Live element priority is assertive.'));
            QUnit.start();
          }, 200);
        };
      },
      announcemultipecalls: function () {
        return function () {
          QUnit.expect(3);
          var $live = $('#drupal-live-announce');
          var text1 = 'From whence the willow tree did spring!';
          var text2 = 'And when the dancing was done!';
          var text3 = 'The working began in earnest?';
          var text4 = 'And the wind it ceased to sing.';

          // Invoke the live region three times in a row.
          Drupal.announce(Drupal.t(text1));
          Drupal.announce(Drupal.t(text2), 'assertive');
          Drupal.announce(Drupal.t(text3));

          // Provide a joined string to compare against.
          var expected = [text1, text2, text3].join('\n');
          // There is a 200ms wait before the text will print.
          QUnit.stop();
          setTimeout(function () {
            // Verify the text.
            QUnit.equal($live.text(), expected, Drupal.t('The ARIA Live element contains the test text.'));
            // Verify that the priority is assertive.
            QUnit.equal($live.attr('aria-live'), 'assertive', Drupal.t('The ARIA Live element priority is assertive.'));
            // Verify that a new string replaces the previous text.
            Drupal.announce(Drupal.t(text4));
            setTimeout(function () {
              QUnit.equal($live.text(), text4, Drupal.t('The ARIA Live element contains the test text.'));
              QUnit.start();
            }, 200);
          }, 200);
        };
      }
    }
  };
}(jQuery, Drupal, this, this.document));
