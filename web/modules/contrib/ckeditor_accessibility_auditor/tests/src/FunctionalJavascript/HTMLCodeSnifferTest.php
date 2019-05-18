<?php

namespace Drupal\Tests\ckeditor_accessibility_auditor\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\editor\Entity\Editor;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\filter\Entity\FilterFormat;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\node\Entity\NodeType;

/**
 * Tests the HTML_Codesniffer button.
 *
 * @group ckeditor_accessibility_auditor
 */
class HTMLCodeSnifferTest extends JavascriptTestBase {

  /**
   * The account.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'ckeditor',
    'filter',
    'ckeditor_accessibility_auditor',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a text format and associate CKEditor.
    $filtered_html_format = FilterFormat::create([
      'format' => 'filtered_html',
      'name' => 'Filtered HTML',
      'weight' => 0,
    ]);
    $filtered_html_format->save();

    Editor::create([
      'format' => 'filtered_html',
      'editor' => 'ckeditor',
      'settings' => [
        'toolbar' => [
          'rows' => [
            [
              [
                'name' => 'Enabled Buttons',
                'items' => [
                  'HTML_CodeSniffer',
                ],
              ],
            ],
          ],
        ],
      ],
    ])->save();

    // Create a node type for testing.
    NodeType::create(['type' => 'page', 'name' => 'page'])->save();

    $field_storage = FieldStorageConfig::loadByName('node', 'body');

    // Create a body field instance for the 'page' node type.
    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'page',
      'label' => 'Body',
      'settings' => ['display_summary' => TRUE],
      'required' => TRUE,
    ])->save();

    // Assign widget settings for the 'default' form mode.
    EntityFormDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => 'page',
      'mode' => 'default',
      'status' => TRUE,
    ])->setComponent('body', ['type' => 'text_textarea_with_summary'])
      ->save();

    $this->account = $this->drupalCreateUser([
      'administer nodes',
      'create page content',
      'use text format filtered_html',
      'access administration pages',
      'administer site configuration',
      'administer filters',
    ]);
    $this->drupalLogin($this->account);
  }

  /**
   * Tests if pressing the button generates a popup.
   */
  public function testAuditorButton() {
    $session = $this->getSession();
    $web_assert = $this->assertSession();

    $this->drupalGet('node/add/page');

    $session->getPage();

    $web_assert->assertWaitOnAjaxRequest();

    // Check that the icon is displayed.
    $markup = file_url_transform_relative(file_create_url(drupal_get_path('module', 'ckeditor_accessibility_auditor') . '/js/plugins/html_codesniffer/icons/html_codesniffer.png'));
    $this->assertRaw($markup);

    // Click the button.
    $this->getSession()->getPage()->find('css', '.cke_button__html_codesniffer_icon')->click();

    // Wait for the wrapper to appear.
    $wrapper = $web_assert->waitForElement('css', '#HTMLCS-wrapper');
    $this->assertTrue(!empty($wrapper), 'HTMLCS wrapper found');
  }

  /**
   * Tests the CKEditor settings for the HTML_Codesniffer button.
   */
  public function testSettings() {
    // Test as admin.
    $this->drupalGet('admin/config/content/formats/manage/filtered_html');
    $this->assertRaw('Enter the URL to use as a base path for loading the HTML_CodeSniffer files.');
    $this->assertRaw('Enter the default standard to be selected in the auditor');
    $this->drupalPostForm(NULL, ['editor[settings][plugins][html_codesniffer][base_url]' => '\\//squizlabs.github.io/HTML_CodeSniffer/build/'], 'Save configuration');
    $this->assertRaw('Please enter a valid Base URL for the Accessibility Auditor.');

    $not_an_admin = $this->drupalCreateUser([
      'administer nodes',
      'create page content',
      'use text format filtered_html',
      'access administration pages',
      'administer filters',
    ]);
    $this->drupalLogin($not_an_admin);
    $this->drupalGet('admin/config/content/formats/manage/filtered_html');
    // Check that settings are disabled for non-admins.
    $this->assertRaw('Only editable by Administrators!');
    $url = 'ajfkJKJhh134hf';
    $this->drupalPostForm(NULL, ['editor[settings][plugins][html_codesniffer][base_url]' => $url], 'Save configuration');
    $this->assertNoRaw('Please enter a valid Base URL for the Accessibility Auditor.');
    $this->drupalGet('admin/config/content/formats/manage/filtered_html');
    $this->assertNoRaw($url);
  }

}
