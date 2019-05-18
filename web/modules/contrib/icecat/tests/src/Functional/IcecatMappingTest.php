<?php

namespace Drupal\Tests\icecat\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\icecat\Entity\IcecatMapping;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests Icecat settings page.
 */
class IcecatMappingTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'icecat',
    'node',
  ];

  /**
   * A test user with administrative privileges.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $permissions = [
      'view the administration theme',
      'access administration pages',
      'administer icecat settings',
      'manage icecat mappings',
    ];

    $this->adminUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->adminUser);

    // Create Basic page node type.
    $this->drupalCreateContentType([
      'type' => 'page',
      'name' => 'Basic page',
    ]);

    // Create a field.
    FieldStorageConfig::create([
      'field_name' => 'field_ean',
      'type' => 'text',
      'entity_type' => 'node',
      'cardinality' => 1,
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_ean',
      'field_type' => 'string',
      'entity_type' => 'node',
      'bundle' => 'page',
      'label' => 'Ean code',
    ])->save();
  }

  /**
   * Tests correct message for empty mapping.
   */
  public function testEmptyMappingList() {
    $this->drupalGet('admin/structure/icecat/mappings');
    $this->assertSession()->pageTextContains('There is no Icecat mapping yet.');
  }

  /**
   * Tests the creation of mapping.
   */
  public function testMappingCreation() {
    $this->drupalGet('admin/structure/icecat/mappings/add');

    // Because the form relies on javascript we have to submit it to populate
    // the fields.
    $form_data = [
      'edit-label' => 'Test mapping',
      'edit-id' => 'test_mapping',
      'edit-example-ean' => 1234567890123,
      'edit-entity-type' => 'node',
    ];
    $this->submitForm($form_data, 'edit-submit');

    $form_data = [
      'edit-entity-type-bundle' => 'page',
    ];
    $this->submitForm($form_data, 'edit-submit');

    $form_data = [
      'edit-data-input-field' => 'field_ean',
    ];
    $this->submitForm($form_data, 'edit-submit');

    // Last submission is done, form should now be complete.
    $this->assertSession()->pageTextContains('Updated the Test mapping mapping.');
    $this->assertSession()->pageTextNotContains('There is no Icecat mapping yet.');
  }

  /**
   * Tests correct message for empty mapping.
   */
  public function testEmptyMappingLinkList() {
    $mapping = new IcecatMapping([
      'id' => 'test_mapping',
      'label' => 'Test maping',
      'example_ean' => 1234567890123,
      'entity_type' => 'node',
      'entity_type_bundle' => 'page',
      'data_input_field' => 'field_ean',
    ]);
    $mapping->save();

    $this->drupalGet('admin/structure/icecat/mappings/test_mapping/links');
    $this->assertSession()->pageTextContains('There is no Icecat mapping link yet.');
  }

  /**
   * Tests the creation of a mapping link.
   */
  public function testMappingLinkCreation() {
    $mapping = new IcecatMapping([
      'id' => 'test_mapping',
      'label' => 'Test maping',
      'example_ean' => 1234567890123,
      'entity_type' => 'node',
      'entity_type_bundle' => 'page',
      'data_input_field' => 'field_ean',
    ]);
    $mapping->save();

    $this->drupalGet('admin/structure/icecat/mappings/test_mapping/links/add');

    // Because the form relies on javascript we have to submit it to populate
    // the fields.
    $form_data = [
      'edit-local-field' => 'body',
    ];
    $this->submitForm($form_data, 'edit-submit');

    $form_data = [
      'edit-remote-field-type' => 'other',
    ];
    $this->submitForm($form_data, 'edit-submit');

    $form_data = [
      'edit-remote-field' => 'getShortDescription',
    ];
    $this->submitForm($form_data, 'edit-submit');

    $this->assertSession()->pageTextContains('Mapping link has been created');

    $this->drupalGet('admin/structure/icecat/mappings/test_mapping/links');

    $this->assertSession()->pageTextNotContains('There is no Icecat mapping link yet.');
  }

}
