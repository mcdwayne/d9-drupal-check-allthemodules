<?php

namespace Drupal\queue_order\Tests\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Class DefinitionsWithoutModuleTest.
 *
 * @package Drupal\queue_order\Tests\Kernel
 *
 * @group queue_order
 */
class DefinitionsWithoutModuleTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['queue_order_definition_fixtures'];

  /**
   * Queue Worker Manager service.
   *
   * @var \Drupal\Core\Queue\QueueWorkerManagerInterface
   */
  protected $QueueWorkerManager;

  protected $orderedList = [
    'queue_order_worker_B',
    'queue_order_worker_A',
    'queue_order_worker_D',
    'queue_order_worker_E',
    'queue_order_worker_C',
    'queue_order_worker_F',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->QueueWorkerManager = $this->container->get('plugin.manager.queue_worker');
  }

  /**
   * Test equality of Queue Worker definition order.
   */
  public function testOrder() {
    $this->assertNotEquals(
      $this->orderedList,
      array_keys($this->QueueWorkerManager->getDefinitions()),
      $this > t('Order is managed by the core functionality')
    );
  }

}
