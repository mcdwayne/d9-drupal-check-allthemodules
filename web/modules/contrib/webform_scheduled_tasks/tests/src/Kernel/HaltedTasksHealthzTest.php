<?php

namespace Drupal\webform_scheduled_tasks\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform_scheduled_tasks\Entity\WebformScheduledTask;

/**
 * Test the halted tasks healthz check.
 *
 * @group webform_scheduled_tasks
 */
class HaltedTasksHealthzTest extends KernelTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'user',
    'webform',
    'webform_scheduled_tasks',
    'webform_scheduled_tasks_test_types',
    'healthz',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('webform', ['webform']);
    $this->installEntitySchema('webform_submission');
    $this->installEntitySchema('user');
  }

  /**
   * Test the interval scheduling.
   */
  public function testHealthCheck() {
    $webform = Webform::create(['id' => 'foo']);
    $schedule = WebformScheduledTask::create([
      'id' => 'foo',
      'result_set_type' => 'all_submissions',
      'task_type' => 'test_task',
      'webform' => $webform->id(),
    ]);
    $schedule->save();

    /** @var \Drupal\healthz\HealthzPluginManager $check_manager */
    $check_manager = \Drupal::service('plugin.manager.healthz');
    $health_check = $check_manager->createInstance('webform_scheduled_tasks_halted');

    $this->assertTrue($health_check->check());
    $schedule->halt('Something broke!');
    $this->assertFalse($health_check->check());
  }

}
