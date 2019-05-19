<?php

namespace Drupal\twitter_embed_field\Tests;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests twitter_embed_field functionality.
 *
 * @group TwitterEmbedField
 */
class TwitterEmbedTest extends BrowserTestBase {


  public static $modules = [
    'node',
    'field',
    'path',
    'twitter_embed_field',
  ];

  protected $strictConfigSchema = FALSE;

  /**
   * The field name used for the twitter field.
   *
   * @var string
   */
  protected $fieldName = 'field_twitter';

  /**
   * Twitter handle to check which should fail validation.
   *
   * @var string
   */
  protected $invalidTwitterHandle = 'invalid twitter handle';

  /**
   * Twitter handle to check.
   *
   * @var string
   */
  protected $twitterHandle = 'TwitterDev';

  /**
   * Twitter handle with @ to check.
   *
   * @var string
   */
  protected $twitterHandleWithAt = '@TwitterDev';

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
      'type' => 'string',
      'entity_type' => 'node',
      'cardinality' => 1,
    ]);

    $field_storage->save();

    FieldConfig::create([
      'field_storage' => $field_storage,
      'field_name' => $this->fieldName,
      'entity_type' => 'node',
      'bundle' => 'page',
      'label' => 'twitter field',
    ])->save();
  }

  /**
   * Test validation and output of the field.
   */
  public function testTwitterField() {

    // Create a form display for the default form mode.
    entity_get_form_display('node', 'page', 'default')
      ->setComponent($this->fieldName, [
        'type' => 'twitter_embed_field',
      ])
      ->save();

    // Create a display for the full view mode with the default settings.
    entity_get_display('node', 'page', 'full')
      ->setComponent($this->fieldName, [
        'type' => 'twitter_embed_field',
      ])
      ->save();

    // Display creation form and check if field is present.
    $this->drupalGet('node/add/page');
    $this->assertSession()->fieldExists("{$this->fieldName}[0][value]");

    // Fill in invalid twitter handle and check validation.
    $edit = [
      "title[0][value]" => 'Test',
      "{$this->fieldName}[0][value]" => $this->invalidTwitterHandle,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertSession()->responseContains(t('<em>@value</em> is no valid twitter handle.', ['@value' => $this->invalidTwitterHandle])->render());

    // Fill in valid twitter handle and check if library gets loaded and
    // the correct markup is created.
    $edit = [
      "title[0][value]" => 'Test',
      "{$this->fieldName}[0][value]" => $this->twitterHandle,
      "path[0][alias]" => '/twitter_test_node',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertSession()->responseContains('<script src="https://platform.twitter.com/widgets.js"></script>');
    $this->assertSession()->responseContains('<a class="twitter-timeline" href="https://twitter.com/' . $this->twitterHandle . '" data-width="300" data-height="300" data-theme="light" data-link-color="#2B7BB9">');

    // Change the default display settings and check if the correct
    // markup is created.
    entity_get_display('node', 'page', 'full')
      ->setComponent($this->fieldName, [
        'type' => 'twitter_embed_field',
        'settings' => [
          'width' => 200,
          'height' => 400,
          'theme' => 'dark',
          'link_color' => '#ffffff',
        ],
      ])
      ->save();
    $this->drupalGet('twitter_test_node');
    $this->assertSession()->responseContains('<a class="twitter-timeline" href="https://twitter.com/' . $this->twitterHandle . '" data-width="200" data-height="400" data-theme="dark" data-link-color="#ffffff">');

    // Fill in twitter handle with @ and check if the correct markup
    // is created.
    $this->drupalGet('node/add/page');
    $edit = [
      "title[0][value]" => 'Test',
      "{$this->fieldName}[0][value]" => $this->twitterHandleWithAt,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertSession()->responseContains('<a class="twitter-timeline" href="https://twitter.com/' . $this->twitterHandleWithAt . '" data-width="200" data-height="400" data-theme="dark" data-link-color="#ffffff">');

  }
}