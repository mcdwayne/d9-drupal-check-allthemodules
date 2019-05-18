<?php

namespace Drupal\Tests\ckeditor_config\FunctionalJavascript;

use Drupal\ckeditor_config\Plugin\CKEditorPlugin\CustomConfig;
use Drupal\filter\Entity\FilterFormat;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Verify settings form validation, submission, and storage.
 *
 * @group ckeditor_congfig
 */
class CkeditorConfigTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'ckeditor',
    'ckeditor_config',
    'editor',
    'filter',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalLogin($this->drupalCreateUser([
      'access administration pages',
      'administer filters',
    ]));
  }

  /**
   * Verify settings form validation, submission, and storage.
   */
  public function testFormSubmission() {
    // Create text format.
    $filtered_html_format = FilterFormat::create([
      'format' => 'testing_text_format',
      'name' => 'Testing Text Format',
      'weight' => 0,
      'filters' => [],
    ]);
    $filtered_html_format->save();

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Navigate to configuration page for testing text format.
    $this->drupalGet("admin/config/content/formats/manage/testing_text_format");
    $page->fillField('edit-editor-editor', 'ckeditor');
    $assert_session->assertWaitOnAjaxRequest();

    // Enter a value that will fail validation.
    $this->clickLink('CKEditor custom configuration');
    $test_value = 'forcePasteAsPlainText = true' . PHP_EOL . 'forceSimpleAmpersand false';
    $page->fillField('editor[settings][plugins][customconfig][ckeditor_custom_config]', $test_value);
    $page->pressButton('Save configuration');
    $assert_session->waitForElement('css', '.messages--error');
    $assert_session->elementTextContains('css', '.messages--error', 'The configuration syntax on line 2 is incorrect.');

    // Enter a value that will pass validation.
    $this->clickLink('CKEditor custom configuration');
    $test_value = 'forcePasteAsPlainText = true' . PHP_EOL . 'forceSimpleAmpersand = false' . PHP_EOL . 'removePlugins = font' . PHP_EOL . 'tabIndex = 3';
    $page->fillField('editor[settings][plugins][customconfig][ckeditor_custom_config]', $test_value);
    $page->pressButton('Save configuration');
    $assert_session->elementTextContains('css', '.messages--status', 'The text format Testing Text Format has been updated.');

    // Verify submitted value is same as value stored in config.
    $editor = editor_load('testing_text_format');
    $settings = $editor->getSettings();
    $stored_value = $settings['plugins']['customconfig']['ckeditor_custom_config'];
    // Normalize line endings in config value.
    $stored_value = str_replace(["\r\n", "\n", "\r"], PHP_EOL, $stored_value);
    $this->assertIdentical($test_value, $stored_value);

    // Verify submitted value is same value provided by plugin's
    // getConfig() method.
    $settings = CustomConfig::getConfig($editor);
    // phpcs:disable Generic.PHP.UpperCaseConstant
    // CKEditor expects booleans in lowercase.
    $this->assertIdentical($settings['forcePasteAsPlainText'], true);
    $this->assertIdentical($settings['forceSimpleAmpersand'], false);
    // phpcs:enable
    $this->assertIdentical($settings['removePlugins'], 'font');
    $this->assertIdentical($settings['tabIndex'], 3);
  }

}
