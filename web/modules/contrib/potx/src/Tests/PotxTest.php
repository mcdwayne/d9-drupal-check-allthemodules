<?php

namespace Drupal\potx\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Ensure that the translation template extractor functions properly.
 *
 * @group potx
 */
class PotxTest extends WebTestBase {

  public static $modules = ['locale', 'potx'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {

    // Add potx.inc which we test for its functionality.
    include_once __DIR__ . '/../../potx.inc';
    include_once __DIR__ . '/../../potx.local.inc';
    potx_local_init();
    // Store empty error message for reuse in multiple cases.
    $this->empty_error = t('Empty string attempted to be localized. Please do not leave test code for localization in your source.');
    $this->tests_root = __DIR__ . '/../../tests';
  }

  /**
   * Test parsing of Drupal 5 module.
   */
  public function testDrupal5() {
    // Parse and build the Drupal 5 module file.
    $filename = $this->tests_root . '/potx_test_5.module';
    $this->parseFile($filename, POTX_API_5);

    // Assert strings found in module source code.
    $this->assertMsgId('Test menu item in 5');
    $this->assertMsgId('This is a test menu item in 5');
    $this->assertMsgId('This is a test string.');
    $this->assertMsgId('test watchdog type');
    // No support for instant t() in watchdog.
    $this->assertNoMsgId('My watchdog message');
    $this->assertMsgId('test potx permission');
    $this->assertMsgId('one more test potx permission');
    $this->assertPluralId('1 test string', '@count test strings');

    // Installer string should not appear in runtime output.
    $this->assertNoMsgId('Installer only test string');
    $this->assertMsgId('Dynamic callback test string');
    $this->assertNoMsgId('Installer string in context');

    // No context support yet.
    $this->assertNoMsgIdContext('Test string in context', 'Test context');
    $this->assertMsgId('Test string in context');
    $this->assertNoMsgIdContext('Dynamic string in context', 'Dynamic context');
    $this->assertMsgId('Dynamic string in context');
    // The singular/plural will not even be found without context, because
    // Drupal 5 does not have support for args on format_plural.
    $this->assertNoMsgId('1 test string in context');
    $this->assertNoPluralIdContext('1 test string in context', '@count test strings in context', 'Test context');

    // Look at installer strings.
    $this->parseFile($filename, POTX_API_5, POTX_STRING_INSTALLER);
    $this->assertMsgId('Installer only test string');
    $this->assertMsgId('Dynamic callback test string');
    $this->assertNoMsgId('1 test string');
    $this->assertNoMsgId('This is a test string.');
    $this->assertNoMsgIdContext('Installer string in context', 'Installer context');
    $this->assertMsgId('Installer string in context');
    $this->assertNoMsgIdContext('Dynamic string in context', 'Dynamic context');
    $this->assertMsgId('Dynamic string in context');

    $this->assertPotxErrors([
      'First empty error' => $this->empty_error,
      'Second empty error' => $this->empty_error,
      'Third empty error' => $this->empty_error,
      'Fourth error' => t('In @function(), the singular and plural strings should be literal strings. There should be no variables, concatenation, constants or even a t() call there.', [
        '@function' => 'format_plural',
      ]),
    ]);
  }

  /**
   * Test parsing of Drupal 6 module.
   */
  public function testDrupal6() {
    // Parse and build the Drupal 6 module file.
    $filename = $this->tests_root . '/potx_test_6.module';
    $this->parseFile($filename, POTX_API_6);

    // Assert strings found in module source code.
    $this->assertMsgId('Test menu item');
    $this->assertMsgId('This is a test menu item');
    $this->assertMsgId('This is a test string.');
    $this->assertMsgId('test watchdog type');
    $this->assertMsgId('My watchdog message');
    $this->assertMsgId('test potx permission');
    $this->assertMsgId('one more test potx permission');
    $this->assertPluralId('1 test string', '@count test strings');
    $this->assertMsgId('Test menu item description');
    $this->assertMsgId('Test menu item description altered (1)');
    $this->assertMsgId('Test menu item description altered (2)');
    $this->assertMsgId('Test menu item title altered');

    // Installer string should not appear in runtime output.
    $this->assertNoMsgId('Installer only test string');
    $this->assertMsgId('Dynamic callback test string');
    $this->assertNoMsgId('Installer string in context');

    // No context support yet.
    $this->assertNoMsgIdContext('Test string in context', 'Test context');
    $this->assertMsgId('Test string in context');
    $this->assertNoMsgIdContext('Dynamic string in context', 'Dynamic context');
    $this->assertMsgId('Dynamic string in context');
    $this->assertPluralId('1 test string in context', '@count test strings in context');
    $this->assertNoPluralIdContext('1 test string in context', '@count test strings in context', 'Test context');

    // Look at installer strings.
    $this->parseFile($filename, POTX_API_6, POTX_STRING_INSTALLER);
    $this->assertMsgId('Installer only test string');
    $this->assertMsgId('Dynamic callback test string');
    $this->assertNoMsgId('1 test string');
    $this->assertNoMsgId('This is a test string.');
    $this->assertNoMsgIdContext('Installer string in context', 'Installer context');
    $this->assertMsgId('Installer string in context');
    $this->assertNoMsgIdContext('Dynamic string in context', 'Dynamic context');
    $this->assertMsgId('Dynamic string in context');

    $this->assertPotxErrors([
      'First empty error' => $this->empty_error,
      'Second empty error' => $this->empty_error,
      'Third empty error' => $this->empty_error,
    ]);
  }

  /**
   * Test parsing of Drupal 7 module.
   */
  public function testDrupal7() {
    // Parse and build the Drupal 7 module file.
    $filename = $this->tests_root . '/potx_test_7.module';
    $this->parseFile($filename, POTX_API_7);

    // Assert strings found in module source code.
    $this->assertMsgId('Test menu item');
    $this->assertMsgId('This is a test menu item');
    $this->assertMsgId('This is a test string.');
    $this->assertMsgId('test watchdog type');
    $this->assertMsgId('My watchdog message');

    // No support for hook_perm() anymore. t() in hook_permissions().
    $this->assertNoMsgId('test potx permission');
    $this->assertNoMsgId('one more test potx permission');
    $this->assertMsgId('Test potx permission');
    $this->assertMsgId('Test potx permission description');
    $this->assertMsgId('One more test potx permission');
    $this->assertMsgId('One more test potx permission description');

    $this->assertPluralId('1 test string', '@count test strings');
    $this->assertPluralIdContext('1 test string in context', '@count test strings in context', 'Test context');

    $this->assertMsgId('Test menu item description');
    $this->assertMsgId('Test menu item description altered (1)');
    $this->assertMsgId('Test menu item description altered (2)');
    $this->assertMsgId('Test menu item title altered');

    $this->assertNoMsgId('Installer only test string');
    $this->assertMsgId('Dynamic callback test string');
    $this->assertNoMsgIdContext('Installer string in context', 'Installer context');
    $this->assertMsgIdContext('Dynamic string in context', 'Dynamic context');

    // Context support added.
    $this->assertMsgIdContext('Test string in context', 'Test context');

    // Drush support.
    $this->assertMsgId('This could have been in a drush file');

    // Look at installer strings.
    $this->parseFile($filename, POTX_API_7, POTX_STRING_INSTALLER);
    $this->assertMsgId('Installer only test string');
    $this->assertMsgId('Dynamic callback test string');
    $this->assertNoMsgId('1 test string');
    $this->assertNoMsgId('This is a test string.');
    $this->assertMsgIdContext('Installer string in context', 'Installer context');
    $this->assertMsgIdContext('Dynamic string in context', 'Dynamic context');

    $this->assertPotxErrors([
      'First empty error' => $this->empty_error,
      'Second empty error' => $this->empty_error,
    ]);
  }

  /**
   * Test parsing of Drupal 7 module with a syntax error.
   */
  public function testDrupal7WithSyntaxError() {
    // Parse and build the Drupal 7 module file.
    $filename = 'potx_test_7_with_error.module';
    $file_content = "
<?php

function potx_test_7_with_syntax_error() {
  t('Oh god why would @you omit a parenthesis?', array('@you' => 'fool');
  t('PHP Syntax error gracefully handled');
}
    ";

    $this->parsePhpContent($file_content, $filename, POTX_API_7);

    $this->assertMsgId('PHP Syntax error gracefully handled');

    $this->assertPotxErrors(['Unexpected semicolon' => t('Unexpected ;')]);
  }

  /**
   * Test parsing of the special DrupalManager class in Drupal 8 core.
   */
  public function testDrupal8LanguageManager() {
    $filename = 'LanguageManager.php';
    $file_content = "
<?php

/**
 * @file
 * Contains potx test class of \Drupal\Core\Language\LanguageManager.
 */

/**
 * Mock class
 */
class PotxMockLanguageManager {

  public static function getStandardLanguageList() {
    return array(
      'af' => array('Test English language', 'Test localized language'),
    );
  }

}
    ";

    $this->parsePhpContent($file_content, $filename, POTX_API_8);

    $this->assertMsgId('Test English language');

    $this->assertPotxErrors([]);
  }

  /**
   * Test parsing of Drupal 8 Twig templates.
   */
  public function testDrupal8Twig() {
    $filename = $this->tests_root . '/potx_test_8.html.twig';
    $this->parseFile($filename, POTX_API_8);

    $this->assertMsgId('This is a translated string.');
    $this->assertMsgId('A double-quoted string');
    $this->assertMsgId('Here\'s a double-quoted "and escaped" string.');
    $this->assertMsgId("Here's a string with an escaped quote.");

    $this->assertNoMsgId('This is a concatenated string that should not be picked up.');
    $this->assertNoMsgId('This is a concatenated string ');
    $this->assertNoMsgId('that should not be picked up.');
    $this->assertNoMsgId('This is an untranslated string.');

    $this->assertMsgId('Hello sun.');
    $this->assertMsgIdContext('Hello sun, with context.', 'Lolspeak');
    $this->assertMsgId('Hello Earth.');
    $this->assertMsgId('Hello moon.');
    $this->assertPluralId('Hello star.', 'Hello @count stars.');

    $this->assertMsgId('Escaped: @string');
    $this->assertMsgId('Placeholder: %string');

    $this->assertMsgId('This @node.type is submitted by @author.name, and has a length of: @count. It contains: %node.numbers and @node.bad_text.');

    $this->assertMsgIdContext('I have context.', 'Lolspeak');
    $this->assertNoMsgIdContext('I have no context.', 'zz');
    $this->assertMsgIdContext('I have context and another parameter.', 'Lolspeak');

    $this->assertMsgId('A multiline\n    trans block.');
    $this->assertMsgId('Test string with @extra_filter');
    $this->assertMsgId('Test string with @multiple_filters');
    $this->assertMsgId('Test string with %multiple_filters');

    $this->assertPotxErrors([
      'Concatenation error' => t('Uses of the t filter in Twig templates should start with a single literal string, and should not be chained.'),
    ]);
  }

  /**
   * Test parsing of Drupal 8 module.
   */
  public function testDrupal8() {
    // Parse and build the Drupal 8 module file.
    $filename = $this->tests_root . '/potx_test_8.module.txt';
    $this->parseFile($filename, POTX_API_8);

    // Test parsing $this->t calls in D8 code.
    $this->assertMsgId('Using t inside D8 classes ($this->t)');

    // Assert strings found in module source code.
    $this->assertMsgId('Good translation annotation');
    $this->assertMsgId('Another good translation annotation');
    $this->assertMsgId('Final good translation annotation');

    $this->assertNoMsgIdContext('Good translation annotation', 'Translation test');

    $this->assertMsgId('Translation in good context');
    $this->assertMsgIdContext('Translation in good context', 'Translation test');

    $this->assertPluralId('1 formatPlural test string', '@count formatPlural test strings');
    $this->assertPluralIdContext('1 formatPlural test string in context', '@count formatPlural test strings in context', 'Test context');

    $this->assertPluralId('1 translation->formatPlural test string', '@count translation->formatPlural test strings');
    $this->assertPluralIdContext('1 translation->formatPlural test string in context', '@count translation->formatPlural test strings in context', 'Test context');

    $this->assertPluralId('1 PluralTranslatableMarkup test string', '@count PluralTranslatableMarkup test strings');
    $this->assertPluralIdContext('1 PluralTranslatableMarkup test string with context', '@count PluralTranslatableMarkup test strings with context', 'Test context');

    $this->assertPluralIdContext(
      '1 PluralTranslatableMarkup test string in context with two @place @holders, short array syntax',
      '@count PluralTranslatableMarkup test strings in context with two @place @holders, short array sytanx',
      'Test context'
    );

    $this->assertMsgId('TranslationWrapper string');
    $this->assertMsgIdContext('TranslationWrapper string with context', 'With context');

    $this->assertMsgId('TranslatableMarkup string');
    $this->assertMsgId('TranslatableMarkup string without context');
    $this->assertMsgIdContext('TranslatableMarkup string with long array context', 'With context');
    $this->assertMsgIdContext('TranslatableMarkup string with short array context', 'With context');
    $this->assertMsgIdContext('TranslatableMarkup string with long array followed by short array context', 'With context');
    $this->assertMsgIdContext('TranslatableMarkup string with complicated tokens', 'With context');
    $this->assertMsgIdContext('TranslatableMarkup string with complicated option tokens', 'With context');
    $this->assertMsgId('TranslatableMarkup string with a @placeholder');
    $this->assertMsgIdContext('TranslatableMarkup string with a @placeholder and context', 'Test context');
    $this->assertMsgIdContext('TranslatableMarkup string with a @placeholder and trailing comma and context', 'Test context');
    $this->assertMsgIdContext('TranslatableMarkup string with two @place @holders and context', 'Test context');
    $this->assertMsgIdContext('TranslatableMarkup string with a @placeholder and short array syntax context', 'Test context');
    $this->assertMsgIdContext('TranslatableMarkup string with a @placeholder and trailing comma and short array syntax context', 'Test context');
    $this->assertMsgIdContext('TranslatableMarkup string with two @place @holders and short array syntax context', 'Test context');

    $this->assertMsgId('Test translatable string inside an inline template');
    $this->assertMsgId('Another test translatable string inside an inline template');
    $this->assertMsgId('A translatable string inside an inline template, with double-quoted "#template" key');

    $this->assertMsgId('Debug message');
    $this->assertMsgId('Info message');
    $this->assertMsgId('Notice message');
    $this->assertMsgId('Warning message');
    $this->assertMsgId('Error message');
    $this->assertMsgId('Critical message');
    $this->assertMsgId('Alert message');
    $this->assertMsgId('Emergency message');
    $this->assertMsgId('Log message');
    $this->assertMsgId('Log message 2');
    $this->assertMsgId('Log message 3');

    $this->assertPotxErrors([
      'Incorrect @Translation' => t('In @Translation, only one, non-empty static string is allowed in double quotes.'),
      'First empty error' => $this->empty_error,
    ]);
  }

  /**
   * Test parsing of Drupal 8 .info.yml files.
   */
  public function testDrupal8InfoYml() {
    $filename = $this->tests_root . '/potx_test_8.info.yml';
    $this->parseFile($filename, POTX_API_8);

    // Look for name, description and package name extracted.
    $this->assertMsgId('Translation template extractor tester');
    $this->assertMsgId('Test description');
    $this->assertMsgId('Test package');

    $this->assertPotxErrors([]);
  }

  /**
   * Test parsing of Drupal 8 .routing.yml files.
   */
  public function testDrupal8RoutingYml() {
    $filename = $this->tests_root . '/potx_test_8.routing.yml';
    $this->parseFile($filename, POTX_API_8);

    // Look for all title can be extracted.
    $this->assertMsgId('Build translation test');
    $this->assertMsgId('Build alternative translation');
    $this->assertMsgIdContext('Translation title in context', 'Title context');

    $this->assertPotxErrors([]);
  }

  /**
   * Test parsing of Drupal 8 local tasks, contextual link and action files.
   */
  public function testDrupal8LocalContextualYml() {
    $filenames = [
      $this->tests_root . '/potx_test_8.links.task.yml',
      $this->tests_root . '/potx_test_8.links.action.yml',
      $this->tests_root . '/potx_test_8.links.contextual.yml',
    ];

    $this->parseFile($filenames[0], POTX_API_8);
    $this->assertMsgId('Local task translation test');
    $this->assertMsgIdContext('Local task translation with context test', 'Local task context');
    $this->assertPotxErrors([]);

    $this->parseFile($filenames[1], POTX_API_8);
    $this->assertMsgId('Local action translation test');
    $this->assertMsgIdContext('Local action translation with context test', 'Local action context');
    $this->assertPotxErrors([]);

    $this->parseFile($filenames[2], POTX_API_8);
    $this->assertMsgId('Test Contextual link');
    $this->assertMsgIdContext('Test Contextual link with context', 'Contextual Context');
    $this->assertPotxErrors([]);
  }

  /**
   * Test parsing of Drupal 8 menu link files.
   */
  public function testDrupal8MenuLinksYml() {
    $this->parseFile($this->tests_root . '/potx_test_8.links.menu.yml', POTX_API_8);
    $this->assertMsgId('Test menu link title');
    $this->assertMsgId('Test menu link description.');
    $this->assertMsgIdContext('Test menu link title with context', 'Menu item context');

    $this->assertPotxErrors([]);
  }

  /**
   * Test parsing of custom yaml files.
   */
  public function testDrupal8CustomYml() {
    $files = _potx_explore_dir($this->tests_root . '/potx_test_8/', '*', POTX_API_8);
    _potx_init_yaml_translation_patterns();
    $this->parseFile($files[0], POTX_API_8);
    $this->assertMsgId('Test custom yaml translatable');
    $this->assertMsgIdContext('Test custom yaml translatable with context', 'Yaml translatable context');
    $this->assertPotxErrors([]);

    // Test that translation patterns for a module won't be used for extracting
    // translatable strings for another module.
    potx_finish_processing('_potx_save_string', POTX_API_8);
    $files = _potx_explore_dir($this->tests_root . '/potx_test_yml/', '*', POTX_API_8);
    $this->parseFile($this->tests_root . '/potx_test_yml/potx_test_8.test2.yml', POTX_API_8);
    $this->assertNoMsgId('Not translatable string');
    $this->assertMsgId('Translatable string');
    $this->assertMsgIdContext('Test custom yaml translatable field with context', 'Yaml translatable context');
    $this->assertPotxErrors([]);

    // Test that custom translation patterns are extracted from subfolders.
    $this->parseFile($this->tests_root . '/potx_test_yml/test_folder/potx_test_8.test3.yml', POTX_API_8);
    $this->assertMsgId('Translatable string inside directory');
    $this->assertPotxErrors([]);
  }

  /**
   * Test parsing of Drupal 8 .breakpoints.yml files.
   */
  public function testDrupal8BreakpointsYml() {
    $filename = $this->tests_root . '/potx_test_8.breakpoints.yml';
    $this->parseFile($filename, POTX_API_8);
    $this->assertMsgId('Mobile');
    $this->assertMsgId('Standard');
    $this->assertMsgId('Some breakpoint group');

    $this->assertPotxErrors([]);
  }

  /**
   * Test parsing of Drupal 8 permissions files.
   */
  public function testDrupal8PermissionsYml() {
    $this->parseFile($this->tests_root . '/potx_test_8.permissions.yml', POTX_API_8);
    $this->assertMsgId('Title potx_test_8_a');
    $this->assertMsgId('Description: potx_test_8_a');
    $this->assertMsgId('Title potx_test_8_b');
    $this->assertNoMsgId('some_callback');

    $this->assertPotxErrors([]);
  }

  /**
   * Test parsing of Drupal 8 shipped configuration files.
   */
  public function testDrupal8ShippedConfiguration() {

    global $_potx_store, $_potx_strings, $_potx_install;
    $_potx_store = $_potx_strings = $_potx_install = [];
    $test_d8_path = $this->tests_root . '/drupal8';

    $files = _potx_explore_dir($test_d8_path, '*', POTX_API_8, TRUE);

    foreach ($files as $file) {
      _potx_process_file($file, 0, '_potx_save_string', '_potx_save_version', POTX_API_8);
    }

    _potx_parse_shipped_configuration('_potx_save_string', POTX_API_8);

    $this->buildOutput(POTX_API_8);

    for ($i = 1; $i < 8; $i++) {
      $this->assertNoMsgId($i . '');
    }

    // Test extraction of config schema labels.
    // Make sure all the 'label' strings are extracted.
    $this->assertMsgId('Test integer');
    $this->assertMsgId('Test string with "quotes"');
    $this->assertMsgId('Mapping integer');
    $this->assertMsgId('Test string in sequence');

    // Make sure other strings are not extracted.
    $this->assertNoMsgId('mapping');
    $this->assertNoMsgId('sequence');

    // Test extraction of shipped config translatables.
    $this->assertMsgId('A string with "translatable: true" property');
    $this->assertMsgIdContext('Y-m-d', 'PHP date format');
    $this->assertMsgIdContext('Test string with context', 'Test context');
    $this->assertMsgIdContext('Test label with context', 'Test label with context');
    $this->assertMsgIdContext('Test overriding context', 'Test context override');
    $this->assertMsgId('Simple mapping name');
    $this->assertMsgId('Simple mapping code');
    $this->assertMsgId('Mapping with type - name');
    $this->assertMsgId('Mapping with type - code');
    $this->assertMsgId('Mapping with type - extra label');
    $this->assertMsgId('Simple sequence - Item 1');
    $this->assertMsgId('Simple sequence - Item 2');
    $this->assertMsgId('Typed sequence - Item 1 name');
    $this->assertMsgId('Typed sequence - Item 1 code');
    $this->assertMsgId('Typed sequence - Item 2 name');
    $this->assertMsgId('Typed sequence - Item 2 code');
    $this->assertMsgId('Sequence of sequence - Description 1');
    $this->assertMsgId('Sequence of sequence - Description 2');
    $this->assertMsgId('BC sequence item 1');
    $this->assertMsgId('BC sequence item 2');
    $this->assertMsgId('Basic variable (text)');
    $this->assertMsgId('Parent variable (text)');
    $this->assertMsgId('Key variable (text)');
    $this->assertMsgId('Complex variable test');

    $this->assertMsgId('Optional config translatable string');
    $this->assertMsgIdContext('Optional config test string with context', 'Test context');

    $this->assertNoMsgId('A simple string');
    $this->assertNoMsgId('A text with "translatable: false" property');
    $this->assertNoMsgId('text');
    $this->assertNoMsgId('custom');
    $this->assertNoMsgId('Basic variable (custom)');
    $this->assertNoMsgId('Parent variable (custom)');
    $this->assertNoMsgId('Key variable (custom)');

    $this->assertPluralId('1 place', '@count places');
    $this->assertPluralId('1 comment', '@count comments', 'Test context');

    $this->assertMsgId('Test boolean based variable');

    $broken_twig_path = __DIR__ . '/../../tests/drupal8/broken_twig.html.twig';
    $this->assertPotxErrors([
      'Broken twig error' => t("Twig parsing error on file @path: @error", [
        '@path' => $broken_twig_path,
        '@error' => 'Unexpected character "&" in "' . $broken_twig_path . '" at line 1.',
      ]),
    ]);
  }

  /**
   * Test parsing Drupal 8 validation constraint messages.
   */
  public function testDrupal8ConstraintMessages() {
    $filename = 'TestConstraint.php';
    $file_content = "
<?php
class TestConstraint {

  public \$message = 'Test message';
  public \$testMessage = 'Test message 2';
  public \$testPluralMessage = '1 test message|@count test message';
  public \$normalVar = 'Not a message for translation';
}
    ";

    $this->parsePhpContent($file_content, $filename, POTX_API_8);

    $this->assertMsgId('Test message');
    $this->assertMsgId('Test message 2');
    $this->assertPluralId('1 test message', '@count test message');
    $this->assertNoMsgId('Not a message for translation');

    $this->assertPotxErrors([]);
  }

  /**
   * Test parsing of Drupal 6 info file. Drupal 5 and 7 have no other rules.
   */
  public function testDrupalInfo() {
    // Parse and build the Drupal 6 module file.
    $filename = $this->tests_root . '/potx_test_6.info';
    $this->parseFile($filename, POTX_API_6);

    // Look for name, description and package name extracted.
    $this->assertMsgId('Translation template extractor tester');
    $this->assertMsgId('Test description');
    $this->assertMsgId('Test package');

    $this->assertPotxErrors([]);
  }

  /**
   * Test parsing of a Drupal JS file.
   */
  public function testDrupalJs() {
    // Parse and build the Drupal JS file (from above Drupal 5).
    $filename = $this->tests_root . '/potx_test.js';
    $this->parseFile($filename, POTX_API_6);

    // Assert strings found in JS source code.
    $this->assertMsgId('Test string in JS');
    $this->assertPluralId('1 test string in JS', '@count test strings in JS');
    $this->assertMsgId('String with @placeholder value');
    $this->assertMsgIdContext('Test string in JS in test context', 'Test context');
    $this->assertMsgIdContext('Test string in JS in context and with @placeholder', 'Test context');
    $this->assertMsgIdContext('Multiline string for the test with @placeholder', 'Test context');

    $this->assertPluralIdContext('1 test string in JS in test context', '@count test strings in JS in test context', 'Test context');
    $this->assertPluralIdContext('1 test string in JS with context and @placeholder', '@count test strings in JS with context and @placeholder', 'Test context');

    $this->assertPotxErrors(['Empty error' => $this->empty_error]);
  }

  /**
   * Parse the given file with the given API version.
   */
  private function parseFile($filename, $api_version, $string_mode = POTX_STRING_RUNTIME) {
    global $_potx_store, $_potx_strings, $_potx_install;
    $_potx_store = $_potx_strings = $_potx_install = [];

    potx_status('set', POTX_STATUS_STRUCTURED);
    _potx_process_file($filename, 0, '_potx_save_string', '_potx_save_version', $api_version);
    _potx_build_files($string_mode, POTX_BUILD_SINGLE, 'general', '_potx_save_string', '_potx_save_version', '_potx_get_header', NULL, NULL, $api_version);

    // Grab .po representation of parsed content.
    ob_start();
    _potx_write_files('potx-test.po');
    $this->potx_output = ob_get_clean();
    // debug(var_export($this->potx_output, TRUE));.
    $this->potx_status = potx_status('get', TRUE);
    // debug(var_export($this->potx_status, TRUE));.
  }

  /**
   * Parse the given file with the given API version.
   */
  private function parsePhpContent($code, $filename, $api_version, $string_mode = POTX_STRING_RUNTIME) {
    global $_potx_store, $_potx_strings, $_potx_install;
    $_potx_store = $_potx_strings = $_potx_install = [];

    $basename = basename($filename);
    $name_parts = pathinfo($basename);

    potx_status('set', POTX_STATUS_STRUCTURED);
    _potx_parse_php_file($code, $filename, '_potx_save_string', $name_parts, $basename, $api_version);
    _potx_build_files($string_mode, POTX_BUILD_SINGLE, 'general', '_potx_save_string', '_potx_save_version', '_potx_get_header', NULL, NULL, $api_version);

    // Grab .po representation of parsed content.
    ob_start();
    _potx_write_files('potx-test.po');
    $this->potx_output = ob_get_clean();
    // debug(var_export($this->potx_output, TRUE));.
    $this->potx_status = potx_status('get', TRUE);
    // debug(var_export($this->potx_status, TRUE));.
  }

  /**
   * Build the output from parsed files.
   */
  private function buildOutput($api_version, $string_mode = POTX_STRING_RUNTIME) {
    potx_status('set', POTX_STATUS_STRUCTURED);
    _potx_build_files($string_mode, POTX_BUILD_SINGLE, 'general', '_potx_save_string', '_potx_save_version', '_potx_get_header', NULL, NULL, $api_version);

    // Grab .po representation of parsed content.
    ob_start();
    _potx_write_files('potx-test.po');
    $this->potx_output = ob_get_clean();
    $this->potx_status = potx_status('get', TRUE);
  }

  /**
   * Assert a msgid construct in the .po file.
   */
  private function assertMsgId($string, $message = '', $group = 'Other') {
    if (!$message) {
      $message = format_string('MsgID "@raw" found', ['@raw' => $string]);
    }
    $this->assert(strpos($this->potx_output, 'msgid "' . _potx_format_quoted_string('"' . $string . '"') . '"') !== FALSE, $message, $group);
  }

  /**
   * Assert the lack of a msgid construct in the .po file.
   */
  private function assertNoMsgId($string, $message = '', $group = 'Other') {
    if (!$message) {
      $message = format_string('MsgID "@raw" not found', ['@raw' => $string]);
    }
    $this->assert(strpos($this->potx_output, 'msgid "' . _potx_format_quoted_string('"' . $string . '"') . '"') === FALSE, $message, $group);
  }

  /**
   * Assert a msgid with context in the .po file.
   */
  private function assertMsgIdContext($string, $context, $message = '', $group = 'Other') {
    if (!$message) {
      $message = format_string('MsgID "@raw" in context "@context" found', ['@raw' => $string, '@context' => $context]);
    }
    $this->assert(strpos($this->potx_output, 'msgctxt "' . _potx_format_quoted_string('"' . $context . '"') . "\"\nmsgid \"" . _potx_format_quoted_string('"' . $string . '"') . '"') !== FALSE, $message, $group);
  }

  /**
   * Assert the lack of a msgid with context in the .po file.
   */
  private function assertNoMsgIdContext($string, $context, $message = '', $group = 'Other') {
    if (!$message) {
      $message = format_string('No MsgID "@raw" in context "@context" found', ['@raw' => $string, '@context' => $context]);
    }
    $this->assert(strpos($this->potx_output, 'msgid "' . _potx_format_quoted_string('"' . $string . '"') . '"' . "\nmsgctxt \"" . _potx_format_quoted_string('"' . $context . '"') . '"') === FALSE, $message, $group);
  }

  /**
   * Assert a msgid_plural construct in the .po file.
   */
  private function assertPluralId($string, $plural, $message = '', $group = 'Other') {
    if (!$message) {
      $message = format_string('Plural ID "@raw" found', ['@raw' => $string]);
    }
    $this->assert(strpos($this->potx_output, 'msgid "' . _potx_format_quoted_string('"' . $string . '"') . "\"\nmsgid_plural \"" . _potx_format_quoted_string('"' . $plural . '"') . '"') !== FALSE, $message, $group);
  }

  /**
   * Assert a msgid_plural with context in the .po file.
   */
  private function assertPluralIdContext($string, $plural, $context, $message = '', $group = 'Other') {
    if (!$message) {
      $message = format_string('Plural ID "@raw" found with context "@context"', ['@raw' => $string, '@context' => $context]);
    }
    $this->assert(strpos($this->potx_output, 'msgctxt "' . _potx_format_quoted_string('"' . $context . '"') . "\"\nmsgid \"" . _potx_format_quoted_string('"' . $string . '"') . "\"\nmsgid_plural \"" . _potx_format_quoted_string('"' . $plural . '"') . '"') !== FALSE, $message, $group);
  }

  /**
   * Assert the lack of msgid_plural with context in the .po file.
   */
  private function assertNoPluralIdContext($string, $plural, $context, $message = '', $group = 'Other') {
    if (!$message) {
      $message = format_string('No plural ID "@raw" found with context "@context"', ['@raw' => $string, '@context' => $context]);
    }
    $this->assert(strpos($this->potx_output, 'msgctxt "' . _potx_format_quoted_string('"' . $context . '"') . "\"\nmsgid \"" . _potx_format_quoted_string('"' . $string . '"') . "\"\nmsgid_plural \"" . _potx_format_quoted_string('"' . $plural . '"') . '"') === FALSE, $message, $group);
  }

  /**
   * Assert the list of expected errors generated by potx from parsing the input files.
   *
   * @param array $expected_errors
   *   The list of expected errors.
   */
  private function assertPotxErrors($expected_errors) {
    $this->assert(count($this->potx_status) == count($expected_errors), count($expected_errors) . ' error messages found');

    $potx_errors = array_column($this->potx_status, 0);
    foreach ($expected_errors as $assert_msg => $error_msg) {
      $key = array_search($error_msg, $potx_errors);

      $this->assert($key !== FALSE, $assert_msg . ' found');

      if ($key !== FALSE) {
        unset($potx_errors[$key]);
      }
    }
  }

  /**
   * Debug functionality until simpletest built-in debugging is backported.
   */
  private function outputScreenContents($description = 'output', $basename = 'output') {
    // This is a hack to get a directory that won't be cleaned up by simpletest.
    $file_dir = file_directory_path() . '/../simpletest_output_pages';
    if (!is_dir($file_dir)) {
      mkdir($file_dir, 0777, TRUE);
    }
    $output_path = "$file_dir/$basename." . $this->randomName(10) . '.html';
    $rv = file_put_contents($output_path, $this->drupalGetContent());
    $this->pass("$description: " . l(t('Contents of result page'), $output_path));
  }

}
