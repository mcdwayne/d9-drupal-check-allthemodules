<?php

namespace Drupal\Tests\entity_pilot_map_config\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests entity pilot mapping fields.
 *
 * @group entity_pilot
 */
class EntityPilotMapConfigFieldTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_pilot_map_config',
    'entity_pilot',
    'serialization',
    'hal',
    'rest',
    'text',
    'filter',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('ep_arrival');
  }

  /**
   * Covers entity_pilot_map_config_install().
   */
  public function testArrivalMappingFieldsAreCreated() {
    // There should be no updates as entity_pilot_map_config_install() should
    // have applied the new fields.
    $this->assertTrue(empty($this->container->get('entity.definition_update_manager')
      ->needsUpdates()['ep_arrival']));
    $this->assertTrue(!empty($this->container->get('entity_field.manager')
      ->getFieldStorageDefinitions('ep_arrival')['mapping_fields']));
    $this->assertTrue(!empty($this->container->get('entity_field.manager')
      ->getFieldStorageDefinitions('ep_arrival')['mapping_bundles']));
  }

}
