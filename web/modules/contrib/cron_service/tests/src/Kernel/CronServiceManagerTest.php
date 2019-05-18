<?php

namespace Drupal\Tests\cron_service\Kernel;

use Drupal\cron_service\CronServiceManagerInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that service manager is integrated in Drupal.
 *
 * @group cron_service
 */
class CronServiceManagerTest extends KernelTestBase {

  static protected $modules = ['cron_service'];

  /**
   * Tests the service exists.
   */
  public function testServiceExists() {
    self::assertInstanceOf(CronServiceManagerInterface::class, $this->container->get('cron_service.manager'));
  }

  /**
   * Test that hook_cron executes the service.
   */
  public function testCronExecutesTheService() {
    $test_object = $this->getMockBuilder(CronServiceManagerInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $test_object->expects(self::atLeastOnce())
      ->method('execute');

    $this->container->set('cron_service.manager', $test_object);

    $this->container->get('module_handler')->invoke('cron_service', 'cron');
  }

}
