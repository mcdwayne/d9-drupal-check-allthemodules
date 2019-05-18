/*jshint strict:true, browser:true, curly:true, eqeqeq:true, expr:true, forin:true, latedef:true, newcap:true, noarg:true, trailing: true, undef:true, unused:true */
/*global Drupal: true, jQuery: true, QUnit:true*/
(function ($, Drupal, window, document, undefined) {
  "use strict";
  /**
  * Tests Drupal.checkPlain().
  */
  Drupal.tests.checkPlain = {
    getInfo: function() {
      return {
        name: 'Drupal.checkPlain()',
        description: 'Tests for Drupal.checkPlain().',
        group: 'System'
      };
    },
    tests: {
      basicstring: function ($, Drupal, window, document, undefined) {
        return function() {
          QUnit.expect(5);

          QUnit.equal(Drupal.checkPlain('test'), 'test', Drupal.t("Nothing gets replaced that doesn't need to be replaced with their escaped equivalent."));
          QUnit.equal(Drupal.checkPlain('"test'), '&quot;test', Drupal.t('Quotes are replaced with their escaped equivalent.'));
          QUnit.equal(Drupal.checkPlain('Test&1'), 'Test&amp;1', Drupal.t('Ampersands are replaced with their escaped equivalent.'));
          QUnit.equal(Drupal.checkPlain('Test>test'), 'Test&gt;test', Drupal.t('Greater-than signs are replaced with their escaped equivalent.'));
          QUnit.equal(Drupal.checkPlain('Test<test'), 'Test&lt;test', Drupal.t('Less-than signs are replaced with their escaped equivalent.'));
        };
      },
      testother: function ($, Drupal, window, document, undefined) {
        return function() {
          QUnit.expect(2);

          QUnit.equal(Drupal.checkPlain(['ampers&', 'q"ote']), 'ampers&amp;,q&quot;ote', Drupal.t('Arrays that need to have replacements have them done.'));
          QUnit.equal(Drupal.checkPlain(1), '1', Drupal.t('Integers are left at their equivalent string value.'));
        };
      },
      testcombined: function ($, Drupal, window, document, undefined) {
        return function() {
          QUnit.expect(2);

          QUnit.equal(Drupal.checkPlain('<tagname property="value">Stuff</tagname>'), '&lt;tagname property=&quot;value&quot;&gt;Stuff&lt;/tagname&gt;', Drupal.t('Full HTML tags are replaced with their escaped equivalent.'));
          QUnit.equal(Drupal.checkPlain('Test "&".'), 'Test &quot;&amp;&quot;.', Drupal.t('A string with both quotes and ampersands replaces those entities with their escaped equivalents.'));
        };
      }
    }
  };

  /**
 * Tests Drupal.t().
 */
  Drupal.tests.drupalt = {
    getInfo: function() {
      return {
        name: 'Drupal.t()',
        description: 'Tests for Drupal.t().',
        group: 'System'
      };
    },
    setup: function() {
      this.originalLocale = Drupal.locale;
      Drupal.locale = Drupal.locale || {};
      Drupal.locale.strings = Drupal.locale.strings || {};
      Drupal.locale = {
        'strings': {
          '' : {
            'Translation 1': '1 noitalsnarT',
            'Translation with a @placeholder': '@placeholder a with Translation',
            'Translation with another %placeholder': '%placeholder in another translation',
            'Literal !placeholder': 'A literal !placeholder',
            'Test unspecified placeholder': 'Unspecified placeholder test'
          }
        }
      };
    },
    teardown: function() {
    // Drupal.locale = this.originalLocale;
    },
    tests: {
      placeholders: function ($, Drupal, window, document, undefined) {
        return function() {
          QUnit.expect(4);

          var html = '<tag attribute="value">Apples & Oranges</tag>';
          var escaped = '&lt;tag attribute=&quot;value&quot;&gt;Apples &amp; Oranges&lt;/tag&gt;';

          QUnit.equal(Drupal.t('Hello world! @html', {
            '@html': html
          }), 'Hello world! ' + escaped, Drupal.t('The "@" placeholder escapes the variable.'));
          QUnit.equal(Drupal.t('Hello world! %html', {
            '%html': html
          }), 'Hello world! <em class="placeholder">' + escaped + '</em>', Drupal.t('The "%" placeholder escapes the variable and themes it as a placeholder.'));
          QUnit.equal(Drupal.t('Hello world! !html', {
            '!html': html
          }), 'Hello world! ' + html, Drupal.t('The "!" placeholder passes the variable through as-is.'));
          QUnit.equal(Drupal.t('Hello world! html', {
            'html': html
          }), 'Hello world! <em class="placeholder">' + escaped + '</em>', Drupal.t('Other placeholders act as "%" placeholders do.'));
        };
      },
      translations: function ($, Drupal, window, document, undefined) {
        return function() {
          QUnit.expect(5);

          QUnit.equal(Drupal.t('Translation 1'), '1 noitalsnarT', Drupal.t('Basic translations work.'));
          QUnit.equal(Drupal.t('Translation with a @placeholder', {
            '@placeholder': '<script>alert("xss")</script>'
          }), '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt; a with Translation', Drupal.t('Translations with the "@" placeholder work.'));
          QUnit.equal(Drupal.t('Translation with another %placeholder', {
            '%placeholder': '<script>alert("xss")</script>'
          }), '<em class="placeholder">&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;</em> in another translation', Drupal.t('Translations with the "%" placeholder work.'));
          QUnit.equal(Drupal.t('Literal !placeholder', {
            '!placeholder': '<script>alert("xss")</script>'
          }), 'A literal <script>alert("xss")</script>', Drupal.t('Translations with the "!" placeholder work.'));
          QUnit.equal(Drupal.t('Test unspecified placeholder', {
            'placeholder': '<script>alert("xss")</script>'
          }), 'Unspecified <em class="placeholder">&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;</em> test', Drupal.t('Translations with unspecified placeholders work.'));
        };
      }
    }
  };

  /**
 * Tests Drupal.attachBehaviors().
 */
  Drupal.tests.drupalBehaviors = {
    getInfo: function() {
      return {
        name: 'Drupal.attachBehaviors()',
        description: 'Tests for Drupal.attachBehaviors().',
        group: 'System'
      };
    },
    setup: function() {
      this.originalBehaviors = Drupal.behaviors;
      var attachIndex = 0;
      var detachIndex = 0;
      Drupal.behaviors = {
        testBehavior: {
          attach: function(context, settings) {
            attachIndex++;
            QUnit.equal(context, 'Attach context ' + attachIndex, Drupal.t('Attach context matches passed context.'));
            QUnit.equal(settings, 'Attach settings ' + attachIndex, Drupal.t('Attach settings match passed settings.'));
          },
          detach: function(context, settings) {
            detachIndex++;
            QUnit.equal(context, 'Detach context ' + detachIndex, Drupal.t('Detach context matches passed context.'));
            QUnit.equal(settings, 'Detach settings ' + detachIndex, Drupal.t('Detach settings match passed settings.'));
          }
        }
      };
    },
    teardown: function() {
      Drupal.behaviors = this.originalBehaviors;
    },
    tests: {
      behaviours: function ($, Drupal, window, document, undefined) {
        return function() {
          QUnit.expect(8);

          // Test attaching behaviors.
          Drupal.attachBehaviors('Attach context 1', 'Attach settings 1');

          // Test attaching behaviors again.
          Drupal.attachBehaviors('Attach context 2', 'Attach settings 2');

          // Test detaching behaviors.
          Drupal.detachBehaviors('Detach context 1', 'Detach settings 1');

          // Try detaching behaviors again.
          Drupal.detachBehaviors('Detach context 2', 'Detach settings 2');
        };
      }
    }
  };

  /**
 * Tests Drupal.attachBehaviors() with a failing behavior. See #1639012
 */
  Drupal.tests.drupalBehaviorsGuard = {
    getInfo: function() {
      return {
        name: 'Drupal.attachBehaviors() Guard',
        description: 'Tests for Drupal.attachBehaviors() with behaviors that throw errors.',
        group: 'System'
      };
    },
    setup: function() {
      this.originalBehaviors = Drupal.behaviors;
      Drupal.behaviors = {
        undefinedVar: {
          attach: function () {
            // Note: this line fails JSHint validation because it is meant to.
            FAIL++;
          }
        },
        throwError: {
          attach: function () {
            throw new Error("Oups");
          }
        },
        validBehavior: {
          attach: function () {
            // Just put an ok statement here. If previous behaviors fail, and there is no guard
            // this behavior will not run, and Qunit will complain: "Expected 1 assertions, but 0 were run".
            QUnit.ok(true, Drupal.t('Behavior ran after failing behaviors.'));
          }
        }
      };
    },
    teardown: function() {
      Drupal.behaviors = this.originalBehaviors;
    },
    tests: {
      behavioursGuard: function ($, Drupal, window, document, undefined) {
        return function() {
          QUnit.expect(1);
          // Test attaching failing behaviors.
          try {
            Drupal.attachBehaviors('Attach context 1', 'Attach settings 1');
          }
          catch(e) {
          // Catch the errors after all behaviors have executed, or the test will fail anyway.
          }
        };
      }
    }
  };
})(jQuery, Drupal, this, this.document);
