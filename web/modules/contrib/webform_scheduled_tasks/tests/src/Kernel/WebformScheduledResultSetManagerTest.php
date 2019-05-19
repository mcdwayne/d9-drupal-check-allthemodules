<?php

namespace Drupal\Tests\webform_scheduled_tasks\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Result set manager test.
 *
 * @coversDefaultClass \Drupal\webform_scheduled_tasks\WebformScheduledResultSetManager
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
    $definitions = $this->container->get('plugin.manager.webform_scheduled_tasks.result_set')->getDefinitions();
    $this->assertArrayHasKey('all_submissions', $definitions);
  }

}
