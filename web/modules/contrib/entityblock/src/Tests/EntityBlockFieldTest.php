<?php

/**
 * @file
 * Contains Drupal\entityblock\Tests\EntityBlockFieldTest.
 */

namespace Drupal\entityblock\Tests;

use Drupal\Component\Utility\Unicode;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\simpletest\WebTestBase;

/**
 * Tests entityblock field widgets and formatters.
 *
 * @group entityblock
 */
class EntityBlockFieldTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['entity_test', 'entityblock', 'node', 'field_ui', 'block'];

  /**
   * A field to use in this test class.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldStorage;

  /**
   * The instance used in this test class.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  protected function setUp() {
    parent::setUp();

    $this->drupalLogin($this->drupalCreateUser([
      'view test entity',
      'administer entity_test content',
    ]));
  }

  /**
   * Tests the link title settings of a link field.
   */
  function testEntityBlock() {
    $field_name = Unicode::strtolower($this->randomMachineName());
    $title = $this->randomString();

    // Create a field with settings to validate.
    $this->fieldStorage = FieldStorageConfig::create(array(
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'entityblock',
      'cardinality' => 1,
    ));
    $this->fieldStorage->save();

    // Set field settings.
    $this->field = FieldConfig::create(array(
      'field_storage' => $this->fieldStorage,
      'bundle' => 'entity_test',
      'label' => 'Block',
      'required' => TRUE,
      'settings' => array(
        'enabled' => 1,
        'title' => $title,
      ),
    ));
    $this->field->save();

    // Set form display.
    \Drupal::entityTypeManager()->getStorage('entity_form_display')->load('entity_test.entity_test.default')
      ->setComponent($field_name, array(
        'type' => 'entityblock_default',
        'settings' => array(
          'view_modes' => array('full' => 'full', 'teaser' => 'teaser'),
          'force_enabled' => FALSE,
          'force_title' => FALSE,
        ),
      ))
      ->save();

    // Display creation form.
    $this->drupalGet('entity_test/add');
    $this->assertFieldByName("{$field_name}[0][enabled]", '', 'Display as block');

    // Test submitting the form without any values.
    $this->drupalPostForm(NULL, [], t('Save'));
    $this->assertText('Display as block field is required.');
    $this->assertText('Block title field is required.');
    $this->assertText('Render the block with this view mode field is required.');

    // Test submitting the form with all values.
    $edit = array(
      "{$field_name}[0][enabled]" => TRUE,
      "{$field_name}[0][title]" => TRUE,
      "{$field_name}[0][view_mode]" => 'full',
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText("entity_test 1 has been created.", 'Entity saved');
  }
}
