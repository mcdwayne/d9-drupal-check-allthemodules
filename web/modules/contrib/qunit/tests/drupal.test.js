(function($) {

/**
 * Tests Drupal.checkPlain().
 */
Drupal.tests.CheckPlain = function() {
};
Drupal.tests.CheckPlain.prototype = new Drupal.UnitTest;

Drupal.tests.CheckPlain.prototype.getInfo = function() {
  return {
    name: 'Check plain',
    description: 'Tests the Drupal.checkPlain() JavaScript function for properly escaping HTML.',
    group: 'System'
  };
};

Drupal.tests.CheckPlain.prototype.test = function() {
  expect(9);

  // Test basic strings.
  equals(Drupal.checkPlain('test'), 'test', Drupal.t("Nothing gets replaced that doesn't need to be replaced with their escaped equivalent."));
  equals(Drupal.checkPlain('"test'), '&quot;test', Drupal.t('Quotes are replaced with their escaped equivalent.'));
  equals(Drupal.checkPlain('Test&1'), 'Test&amp;1', Drupal.t('Ampersands are replaced with their escaped equivalent.'));
  equals(Drupal.checkPlain('Test>test'), 'Test&gt;test', Drupal.t('Greater-than signs are replaced with their escaped equivalent.'));
  equals(Drupal.checkPlain('Test<test'), 'Test&lt;test', Drupal.t('Less-than signs are replaced with their escaped equivalent.'));

  // Test other data types.
  equals(Drupal.checkPlain(['ampers&', 'q"ote']), 'ampers&amp;,q&quot;ote', Drupal.t('Arrays that need to have replacements have them done.'));
  equals(Drupal.checkPlain(1), '1', Drupal.t('Integers are left at their equivalent string value.'));

  // Combined tests.
  equals(Drupal.checkPlain('<tagname property="value">Stuff</tagname>'), '&lt;tagname property=&quot;value&quot;&gt;Stuff&lt;/tagname&gt;', Drupal.t('Full HTML tags are replaced with their escaped equivalent.'));
  equals(Drupal.checkPlain('Test "&".'), 'Test &quot;&amp;&quot;.', Drupal.t('A string with both quotes and ampersands replaces those entities with their escaped equivalents.'));
};

/**
 * Tests Drupal.t().
 */
Drupal.tests.T = function() {
};
Drupal.tests.T.prototype = new Drupal.UnitTest;

Drupal.tests.T.prototype.getInfo = function() {
  return {
    name: Drupal.t('Translation'),
    description: Drupal.t('Tests the basic translation functionality of the Drupal.t() function, including the proper handling of variable strings.'),
    group: Drupal.t('System')
  };
};

Drupal.tests.T.prototype.setup = function() {
  this.originalLocale = Drupal.locale;
  Drupal.locale = {
    'strings': {
      'Translation 1': '1 noitalsnarT',
      'Translation with a @placeholder': '@placeholder a with Translation',
      'Translation with another %placeholder': '%placeholder in another translation',
      'Literal !placeholder': 'A literal !placeholder',
      'Test unspecified placeholder': 'Unspecified placeholder test'
    }
  };
};

Drupal.tests.T.prototype.test = function() {
  expect(9);

  var html = '<tag attribute="value">Apples & Oranges</tag>';
  var escaped = '&lt;tag attribute=&quot;value&quot;&gt;Apples &amp; Oranges&lt;/tag&gt;';

  // Test placeholders.
  equals(Drupal.t('Hello world! @html', {'@html': html}), 'Hello world! ' + escaped, Drupal.t('The "@" placeholder escapes the variable.'));
  equals(Drupal.t('Hello world! %html', {'%html': html}), 'Hello world! <em class="placeholder">' + escaped + '</em>', Drupal.t('The "%" placeholder escapes the variable and themes it as a placeholder.'));
  equals(Drupal.t('Hello world! !html', {'!html': html}), 'Hello world! ' + html, Drupal.t('The "!" placeholder passes the variable through as-is.'));
  equals(Drupal.t('Hello world! html', {'html': html}), 'Hello world! <em class="placeholder">' + escaped + '</em>', Drupal.t('Other placeholders act as "%" placeholders do.'));

  // Test actual translations.
  equals(Drupal.t('Translation 1'), '1 noitalsnarT', Drupal.t('Basic translations work.'));
  equals(Drupal.t('Translation with a @placeholder', {'@placeholder': '<script>alert("xss")</script>'}), '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt; a with Translation', Drupal.t('Translations with the "@" placeholder work.'));
  equals(Drupal.t('Translation with another %placeholder', {'%placeholder': '<script>alert("xss")</script>'}), '<em class="placeholder">&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;</em> in another translation', Drupal.t('Translations with the "%" placeholder work.'));
  equals(Drupal.t('Literal !placeholder', {'!placeholder': '<script>alert("xss")</script>'}), 'A literal <script>alert("xss")</script>', Drupal.t('Translations with the "!" placeholder work.'));
  equals(Drupal.t('Test unspecified placeholder', {'placeholder': '<script>alert("xss")</script>'}), 'Unspecified <em class="placeholder">&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;</em> test', Drupal.t('Translations with unspecified placeholders work.'));
};

Drupal.tests.T.prototype.teardown = function() {
  Drupal.locale = this.originalLocale;
};

/**
 * Tests Drupal.attachBehaviors() and Drupal.detachBehaviors.
 */
Drupal.tests.Behaviors = function() {
};
Drupal.tests.Behaviors.prototype = new Drupal.UnitTest;

Drupal.tests.Behaviors.prototype.getInfo = function() {
  return {
    name: 'JavaScript behaviors',
    description: 'Tests the functionality of Drupal behaviors to make sure it allows JavaScript files to attach and detach behaviors in different contexts.',
    group: 'System'
  };
};

Drupal.tests.Behaviors.prototype.setup = function() {
  this.originalBehaviors = Drupal.behaviors;
  var attachIndex = 0;
  var detachIndex = 0;
  Drupal.behaviors = {
    testBehavior: {
      attach: function(context, settings) {
        attachIndex++;
        equals(context, 'Attach context ' + attachIndex, Drupal.t('Attach context matches passed context.'));
        equals(settings, 'Attach settings ' + attachIndex, Drupal.t('Attach settings match passed settings.'));
      },
      detach: function(context, settings) {
        detachIndex++;
        equals(context, 'Detach context ' + detachIndex, Drupal.t('Detach context matches passed context.'));
        equals(settings, 'Detach settings ' + detachIndex, Drupal.t('Detach settings match passed settings.'));
      }
    }
  };
};

Drupal.tests.Behaviors.prototype.test = function() {
  expect(8);

  // Test attaching behaviors.
  Drupal.attachBehaviors('Attach context 1', 'Attach settings 1');

  // Test attaching behaviors again.
  Drupal.attachBehaviors('Attach context 2', 'Attach settings 2');

  // Test detaching behaviors.
  Drupal.detachBehaviors('Detach context 1', 'Detach settings 1');

  // Try detaching behaviors again.
  Drupal.detachBehaviors('Detach context 2', 'Detach settings 2');
};

Drupal.tests.Behaviors.prototype.teardown = function() {
  Drupal.behaviors = this.originalBehaviors;
};

/**
 * Tests Drupal.encodePath().
 */
Drupal.tests.EncodePath = function() {
};
Drupal.tests.EncodePath.prototype = new Drupal.UnitTest;

Drupal.tests.EncodePath.prototype.getInfo = function() {
  return {
    name: 'Encode path',
    description: 'Tests the Drupal.encodePath() JavaScript function for properly encoding paths.',
    group: 'System'
  };
};

Drupal.tests.EncodePath.prototype.test = function() {
  expect(9);

  // Test basic strings.
  equals(Drupal.encodePath('/foo/bar'), '/foo/bar');
  equals(Drupal.encodePath('"test'), '%22test');
  equals(Drupal.encodePath('Test&1'), 'Test%261');
  equals(Drupal.encodePath('Test>test'), 'Test%3Etest');
  equals(Drupal.encodePath('Test<test'), 'Test%3Ctest');

  // Test other data types.
  equals(Drupal.encodePath(['abc&', 'def?']), 'abc%26%2Cdef%3F');
  equals(Drupal.encodePath(1), '1');

  // Combined tests.
  equals(Drupal.encodePath('http://example.com/foo/bar?example=foobar'), 'http%3A//example.com/foo/bar%3Fexample%3Dfoobar');
  equals(Drupal.encodePath('search/node?keys=search/with/slashes/"and / quotes"'), 'search/node%3Fkeys%3Dsearch/with/slashes/%22and%20/%20quotes%22');
};

/**
 * Tests JavaScript theming.
 */
Drupal.tests.Theme = function() {
};

Drupal.tests.Theme.prototype = new Drupal.UnitTest;

Drupal.tests.Theme.prototype.getInfo = function() {
  return {
    name: 'Theme',
    description: 'Tests the JavaScript implementation of the Drupal theming layer.',
    group: 'System'
  };
};

Drupal.tests.Theme.prototype.test = function() {
  var themeBackup = Drupal.theme;
  // Theme overides.
  Drupal.theme.prototype.example = Drupal.theme.prototype.placeholder;
  equals(Drupal.theme('example', '<example>'), '<em class="placeholder">&lt;example&gt;</em>');
  Drupal.theme.prototype.example = function (str) {
    return 'Test !!' + str + '?!';
  };
  equals(Drupal.theme('example', '<example>'), 'Test !!<example>?!');

  // Theme arguments.
  var args = [];
  Drupal.theme.prototype.argCheck = function (a1, a2, a3, a4, a5, a6, a7, a8, a9, a10) {
    args = [a1, a2, a3, a4, a5, a6, a7, a8, a9, a10];
  }
  Drupal.theme('argCheck', 15, 'foo', 'bar', 'baz', null, undefined, 'http://example.com/', 3.14159, 'abc', 'def');
  var index;
  var bigArray = [15, 'foo', 'bar', 'baz', null, undefined, 'http://example.com/', 3.14159, 'abc', 'def'];
  for (index in bigArray) {
    equals(args[index], bigArray[index]);
  }
  Drupal.theme = themeBackup;
};

})(jQuery);
