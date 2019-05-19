<?php

namespace Drupal\Tests\webform_scheduled_tasks\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform_scheduled_tasks\Entity\WebformScheduledTask;

/**
 * Scheduled task dependencies test.
 *
 * @group webform_scheduled_tasks
 */
class WebformScheduledTaskDependenciesTest extends KernelTestBase {


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
   * Test the dependencies calculation.
   */
  public function testDependenciesCalculation() {
    $schedule_webform = Webform::create([
      'id' => 'scheduled_webform',
    ]);
    $schedule_webform->save();

    $schedule = WebformScheduledTask::create([
      'id' => 'test_task',
      'webform' => $schedule_webform->id(),
      'result_set_type' => 'all_submissions',
      'task_type' => 'test_task',
    ]);
    $schedule->calculateDependencies();
    $schedule->save();

    $this->assertEquals($schedule->getWebform()->id(), $schedule->getWebform()->id());
    $this->assertTrue(in_array('webform.webform.scheduled_webform', $schedule->getDependencies()['config']));

    $schedule_webform->delete();
    $this->assertNull(WebformScheduledTask::load('test_task'));
  }

}
