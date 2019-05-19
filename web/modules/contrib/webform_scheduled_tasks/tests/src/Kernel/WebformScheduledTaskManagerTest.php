<?php

namespace Drupal\Tests\webform_scheduled_tasks\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\webform_scheduled_tasks\WebformScheduledTaskManager
 * @group webform_scheduled_tasks
 */
class WebformScheduledResultSetManagerTest extends KernelTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'webform_scheduled_tasks',
    'webform_scheduled_tasks_test_types',
  ];

  /**
   * @covers ::getDefinitions
   */
  public function testGetDefinitions() {
    $definitions = $this->container->get('plugin.manager.webform_scheduled_tasks.task')->getDefinitions();
    $this->assertArrayHasKey('test_task', $definitions);
  }

}
