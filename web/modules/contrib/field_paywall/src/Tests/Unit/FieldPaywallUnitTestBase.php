<?php

/**
 * @file
 * Contains \Drupal\field_paywall\Tests\Unit\FieldPaywallUnitTestBase.
 */

namespace Drupal\field_paywall\Tests\Unit;

use Drupal\Core\Entity\EntityInterface;
use Drupal\field\Tests\FieldUnitTestBase;

/**
 * Common class for paywall unit tests.
 */
abstract class FieldPaywallUnitTestBase extends FieldUnitTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('field_paywall');

  /**
   * The paywall field definition in use.
   *
   * @var \Drupal\field\Entity\FieldConfig;
   */
  protected $paywallFieldDefinition = NULL;

  /**
   * The paywall field storage config.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig;
   */
  protected $paywallFieldStorageConfig = NULL;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->createPaywallField();
  }

  /**
   * Create the paywall field.
   */
  protected function createPaywallField() {
    $this->paywallFieldStorageConfig = entity_create('field_storage_config', array(
      'field_name' => 'field_paywall',
      'entity_type' => 'entity_test',
      'type' => 'paywall',
    ));
    $this->paywallFieldStorageConfig->save();

    $field_config = entity_create('field_config', array(
      'entity_type' => 'entity_test',
      'field_name' => 'field_paywall',
      'bundle' => 'entity_test',
    ));
    $field_config->save();

    $entity_manager = $this->container->get('entity.manager');
    $definitions = $entity_manager->getFieldDefinitions('entity_test', 'entity_test');

    $this->paywallFieldDefinition = $definitions['field_paywall'];
  }

  /**
   * Create a test entity with paywall.
   *
   * @param bool $paywall_enabled
   *   Whether or not the paywall should be enabled.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The test entity.
   */
  protected function createTestEntity($paywall_enabled = TRUE) {
    // Verify entity creation.
    $entity = entity_create('entity_test');

    $value = $paywall_enabled ? 1 : 0;
    $entity->field_paywall = $value;
    $entity->name->value = $this->randomMachineName();
    $entity->save();

    return $entity;
  }

  /**
   * Retrieve the field item base from a given Entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The Entity to get the field item base from.
   *
   * @return \Drupal\field_paywall\Plugin\Field\FieldType\PaywallItem
   *   The paywall item base.
   */
  protected function getFieldItemBaseFromEntity(EntityInterface $entity) {
    $field_item_base = $entity->get('field_paywall')->first();

    return $field_item_base;
  }

  /**
   * Retrieve the field item base from a given Entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The Entity to get the field item base from.
   *
   * @return \Drupal\field_paywall\Plugin\Field\FieldWidget\PaywallWidget
   *   The paywall item base.
   */
  protected function getFieldWidgetFromEntity(EntityInterface $entity) {
    $widget = \Drupal::service('plugin.manager.field.widget')
      ->getInstance(array('field_definition' => $this->paywallFieldDefinition));

    return $widget;
  }

  /**
   * Create a basic string textfield and attach to the entity bundle.
   *
   * @param string $field_name
   *   The field name to create.
   */
  protected function createBasicTextField($field_name) {
    entity_create('field_storage_config', array(
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'string',
      'cardinality' => 1,
    ))->save();

    entity_create('field_config', array(
      'entity_type' => 'entity_test',
      'field_name' => $field_name,
      'bundle' => 'entity_test',
    ))->save();

    entity_get_display('entity_test', 'entity_test', 'default')
      ->setComponent($field_name)
      ->save();
  }

}