<?php

namespace Drupal\Tests\klipfolio_field\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\BrowserTestBase;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests the creation of Klipfolio fields.
 *
 * @group Klipfolio
 */
class KlipfolioFieldTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'field',
    'node',
    'klipfolio_field',
  ];

  /**
   * A user with permission to create articles.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * Setup our test.
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'article']);
    $this->webUser = $this->drupalCreateUser(['create article content', 'edit own article content']);
    $this->drupalLogin($this->webUser);
  }

  /**
   * Helper function for testKlipfolioField().
   */
  public function testKlipfolioField() {

    // Add the Klipfolio field to the article content type.
    FieldStorageConfig::create([
      'field_name' => 'field_klipfolio',
      'entity_type' => 'node',
      'type' => 'klipfolio',
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_klipfolio',
      'label' => 'Klipfolio Klip',
      'entity_type' => 'node',
      'bundle' => 'article',
    ])->save();

    entity_get_form_display('node', 'article', 'default')
      ->setComponent('field_klipfolio', [
        'type' => 'klipfolio_widget',
        'settings' => [
          'value' => '123-456-7890',
        ],
      ])
      ->save();

    entity_get_display('node', 'article', 'default')
      ->setComponent('field_klipfolio', [
        'type' => 'klipfolio_field_formatter',
        'weight' => 1,
      ])
      ->save();

    // Display creation form.
    $this->drupalGet('node/add/article');
    $this->assertFieldByName("field_klipfolio[0][value]", '', 'Widget found.');

    // Test basic entry of Klipfolio field.
    $edit = [
      'title[0][value]' => $this->randomMachineName(),
      'field_klipfolio[0][value]' => "123456789",
    ];

    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertRaw('id="kf-embed-container-123456789"', 'A Klipfolio ID is found on the article node page.');

  }

}
