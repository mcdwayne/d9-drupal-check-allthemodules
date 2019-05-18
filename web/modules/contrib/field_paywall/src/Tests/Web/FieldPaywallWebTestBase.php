<?php

/**
 * @file
 * Contains \Drupal\field_paywall\Tests\Web\FieldPaywallUnitTestBase.
 */

namespace Drupal\field_paywall\Tests\Web;

use Drupal\field\Tests\FieldTestBase;

/**
 * Base class for Paywall field module integration tests.
 */
abstract class FieldPaywallWebTestBase extends FieldTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('field_paywall', 'node');

  /**
   * The field name used in the test.
   *
   * @var string
   */
  protected $fieldName = 'test_paywall';

  /**
   * The paywall message used in the test.
   *
   * @var string
   */
  protected $message = 'Test message';


  /**
   * The fields hidden by the paywall
   *
   * @var array
   */
  protected $hiddenFields = array('field_1', 'field_2');


  /**
   * The fields not hidden by the paywall
   *
   * @var array
   */
  protected $visibleFields = array('field_3');

  /**
   * The field storage definition used to created the field storage.
   *
   * @var array
   */
  protected $fieldStorageDefinition;

  /**
   * The list field storage used in the test.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldStorage;

  /**
   * The list field used in the test.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create Basic page and Article node types.
    $this->drupalCreateContentType(array(
      'type' => 'article',
      'name' => 'Article'
    ));

    $this->createHiddenFields();
    $this->createVisibleFields();
    $this->createPaywallField();
    $this->setPaywallDisplayOptions();
  }

  /**
   * Create the paywall field.
   */
  protected function createPaywallField() {
    $this->fieldStorageDefinition = array(
      'field_name' => $this->fieldName,
      'entity_type' => 'node',
      'type' => 'paywall',
      'cardinality' => 1,
      'settings' => array(),
    );
    $this->fieldStorage = entity_create('field_storage_config', $this->fieldStorageDefinition);
    $this->fieldStorage->save();

    $this->field = entity_create('field_config', array(
      'field_storage' => $this->fieldStorage,
      'bundle' => 'article',
    ));
    $this->field->save();
  }

  /**
   * Set the paywall display options for the test entity.
   */
  protected function setPaywallDisplayOptions() {
    $display_options = array(
      'settings' => array(
        'message' => $this->message,
        'hidden_fields' => $this->hiddenFields,
      ),
    );

    $display = entity_get_display('node', 'article', 'default');
    $display->setComponent($this->fieldName, $display_options)
      ->save();
  }

  /**
   * Create the hidden fields for the test.
   */
  protected function createHiddenFields() {
    foreach ($this->hiddenFields as $hidden_field_name) {
      $this->createBasicTextField($hidden_field_name);
    }
  }

  /**
   * Create the visible fields for the test.
   */
  protected function createVisibleFields() {
    foreach ($this->visibleFields as $visible_field_name) {
      $this->createBasicTextField($visible_field_name);
    }
  }

  /**
   * Create a basic string textfield and attach to the entity bundle.
   *
   * @param string $field_name
   *   The field name to create.
   */
  protected function createBasicTextField($field_name) {
    $fieldStorageDefinition = array(
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => 'string',
      'cardinality' => 1,
      'settings' => array(),
    );
    $fieldStorage = entity_create('field_storage_config', $fieldStorageDefinition);
    $fieldStorage->save();

    $field = entity_create('field_config', array(
      'field_storage' => $fieldStorage,
      'bundle' => 'article',
    ));
    $field->save();

    entity_get_display('node', 'article', 'default')
      ->setComponent($field_name)
      ->save();
  }

  /**
   * Create a prepopulated entity with an active paywall for testing.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The created entity.
   */
  protected function createEntityWithValues() {
    $entity = entity_create('node');

    foreach ($this->visibleFields as $visible_field_name) {
      $entity->{$visible_field_name}->value = $this->randomMachineName();
    }

    foreach ($this->hiddenFields as $hidden_field_name) {
      $entity->{$hidden_field_name}->value = $this->randomMachineName();
    }

    $entity->{$this->fieldName}->value = array(
      'enabled' => TRUE,
    );

    $entity->save();

    return $entity;
  }
}
