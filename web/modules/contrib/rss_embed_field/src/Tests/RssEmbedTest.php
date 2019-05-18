<?php

namespace Drupal\rss_embed_field\Tests;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\link\LinkItemInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests rss_embed_field functionality.
 *
 * @group RssEmbedField
 */
class RssEmbedTest extends BrowserTestBase {

  public static $modules = [
    'node',
    'field',
    'link',
    'path',
    'rss_embed_field',
  ];

  protected $strictConfigSchema = FALSE;

  /**
   * The field name used for the link field.
   *
   * @var string
   */
  protected $fieldName = 'field_rss_test';

  /**
   * The URL that should fail validation.
   *
   * @var string
   */
  protected $nonFeedUrl;

  /**
   * The URL to the test feed.
   *
   * @var string
   */
  protected $feedUrl;

  /**
   * Test setup.
   */
  protected function setUp() {
    parent::setUp();

    // Create admin user.
    $this->drupalLogin($this->drupalCreateUser([
      'administer content types',
      'create url aliases',
    ]));

    // Create Basic page node type.
    $this->drupalCreateContentType([
      'type' => 'page',
      'name' => 'Test page',
    ]);

    // Create a field.
    $field_storage = FieldStorageConfig::create([
      'field_name' => $this->fieldName,
      'type' => 'link',
      'module' => 'link',
      'entity_type' => 'node',
      'cardinality' => 1,
    ]);

    $field_storage->save();

    $settings = [
      'link_type' => LinkItemInterface::LINK_EXTERNAL,
      'title' => 0,
    ];
    FieldConfig::create([
      'field_storage' => $field_storage,
      'field_name' => $this->fieldName,
      'entity_type' => 'node',
      'bundle' => 'page',
      'label' => 'link field',
      'settings' => $settings,
    ])->save();

    // Build test urls.
    $this->nonFeedUrl = 'https://drupal.org';
    $this->feedUrl = 'https://drupal.org/planet/rss.xml';
  }

  /**
   * Tests validation and output of the field.
   */
  public function testRssEmbedField() {

    // Create a form display for the default form mode.
    entity_get_form_display('node', 'page', 'default')
      ->setComponent($this->fieldName, [
        'type' => 'rss_embed_field',
      ])
      ->save();

    // Create a display for the full view mode with default settings.
    entity_get_display('node', 'page', 'full')
      ->setComponent($this->fieldName, [
        'type' => 'rss_embed_field',
      ])
      ->save();


    $this->drupalGet('rss_embed_field_test.xml');

    // Display creation form and check if field is present.
    $this->drupalGet('node/add/page');
    $this->assertSession()->fieldExists("{$this->fieldName}[0][uri]");

    // Fill in invalid RSS url and check validation.
    $edit = [
      "title[0][value]" => 'Test',
      "{$this->fieldName}[0][uri]" => $this->nonFeedUrl,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertSession()->pageTextContains(t('Loading RSS feed failed.'));

    // Fill in valid RSS url and check if correct markup is created.
    $edit = [
      "title[0][value]" => 'Test',
      "{$this->fieldName}[0][uri]" => $this->feedUrl,
      "path[0][alias]" => '/rss_test_node',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertSession()->responseContains('<strong class="rss-embed-field-feed-title">Drupal.org aggregator</strong>');
    $items = $this->xpath('//li[@class="rss-embed-field-feed-item"]');
    $this->assertEquals(10, count($items));

    // Change default display settings and check if correct markup
    // is created.
    entity_get_display('node', 'page', 'full')
      ->setComponent($this->fieldName, [
        'type' => 'rss_embed_field',
        'settings' => [
          'show_title' => FALSE,
          'items' => 5,
        ],
      ])
      ->save();
    $this->drupalGet('rss_test_node');
    $this->assertSession()->responseNotContains('<strong class="rss-embed-field-feed-title">Drupal.org aggregator</strong>');
    $items = $this->xpath('//li[@class="rss-embed-field-feed-item"]');
    $this->assertEquals(5, count($items));
  }
}