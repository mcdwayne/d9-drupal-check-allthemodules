<?php

namespace Drupal\Tests\telephone_type\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\BrowserTestBase;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests the creation of telephone_type fields.
 *
 * @group telephone
 */
class TelephoneTypeFieldTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'field',
    'node',
    'telephone_type',
  ];

  /**
   * A user with permission to create articles.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * Protected function setUp.
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'article']);
    $this->webUser = $this->drupalCreateUser(['create article content', 'edit own article content']);
    $this->drupalLogin($this->webUser);
  }

  /**
   * Helper function for testTelephoneTypeField().
   */
  public function testTelephoneTypeField() {
    // Add the telephone field to the article content type.
    FieldStorageConfig::create([
      'field_name' => 'field_telephone',
      'entity_type' => 'node',
      'type' => 'telephone_type',
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_telephone',
      'label' => 'Telephone Number',
      'entity_type' => 'node',
      'bundle' => 'article',
    ])->save();

    entity_get_form_display('node', 'article', 'default')
      ->setComponent('field_telephone', [
        'type' => 'telephone_type_default',
        'settings' => [
          'placeholder' => '123-456-7890',
          'types' => ['Work', 'Voice', 'Fax'],
          'type_required' => TRUE,
        ],
      ])
      ->save();

    entity_get_display('node', 'article', 'default')
      ->setComponent('field_telephone', [
        'type' => 'telephone_type_link',
        'weight' => 1,
      ])
      ->save();

    // Display creation form.
    $this->drupalGet('node/add/article');
    $this->assertFieldByName("field_telephone[0][value]", '', 'Widget found.');
    $this->assertRaw('placeholder="123-456-7890"');

    // Test basic entry of telephone field.
    $edit = [
      'title[0][value]' => $this->randomMachineName(),
      'field_telephone[0][value]' => "123456789",
    ];

    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertRaw('<a href="tel:123456789">', 'A telephone link is provided on the article node page.');

    // Add number with a space in it. Need to ensure it is stripped on output.
    $edit = [
      'title[0][value]' => $this->randomMachineName(),
      'field_telephone[0][value]' => "1234 56789",
    ];

    $this->drupalPostForm('node/add/article', $edit, t('Save'));
    $this->assertRaw('<a href="tel:123456789">', 'Telephone link is output with whitespace removed.');
  }

}
