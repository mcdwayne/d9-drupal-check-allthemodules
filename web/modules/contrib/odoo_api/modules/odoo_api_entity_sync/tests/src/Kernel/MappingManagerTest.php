<?php

namespace Drupal\Tests\odoo_api_entity_sync\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\odoo_api_entity_sync\MappingManagerInterface;

/**
 * Tests the MappingManager.
 *
 * @group odoo_api
 */
class MappingManagerTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['odoo_api', 'odoo_api_entity_sync'];

  /**
   * The mapping manager.
   *
   * @var \Drupal\odoo_api_entity_sync\MappingManagerInterface
   */
  protected $mappingManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('odoo_api_entity_sync', 'odoo_api_entity_sync');
    $this->mappingManager = $this->container->get('odoo_api_entity_sync.mapping');
  }

  /**
   * Tests \Drupal\odoo_api_entity_sync\MappingManager::findMappedEntities().
   */
  public function testFindMappedEntities() {
    $odoo_id = 1;
    $odoo_model = 'sale.order';
    $expected_result = [
      $odoo_id => [
        'commerce_order' => [
          'default' => 1,
          'order_status' => 1,
        ],
      ],
    ];

    foreach ($expected_result as $odoo_id => $entity_types) {
      foreach ($entity_types as $entity_type => $export_types) {
        foreach ($export_types as $export_type => $entity_id) {
          $this->mappingManager->setSyncStatus($entity_type, $odoo_model, $export_type, [
            $entity_id => $odoo_id,
          ], MappingManagerInterface::STATUS_SYNCED);
        }
      }
    }

    $actual_result = $this->mappingManager->findMappedEntities($odoo_model, [$odoo_id]);
    $this->assertEquals($expected_result, $actual_result);
  }

}
