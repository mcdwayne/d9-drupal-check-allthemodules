<?php

namespace Drupal\webform_scheduled_tasks\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform_scheduled_tasks\Entity\WebformScheduledTask;

/**
 * Test halting and resume the schedule.
 *
 * @group webform_scheduled_tasks
 */
class ScheduleHaltResumeTest extends KernelTestBase {

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
   * Test the interval scheduling.
   */
  public function testHaltResume() {
    $webform = Webform::create(['id' => 'foo']);
    $schedule = WebformScheduledTask::create([
      'id' => 'foo',
      'result_set_type' => 'all_submissions',
      'task_type' => 'test_task',
      'webform' => $webform->id(),
    ]);
    $schedule->save();
    $unrelated_schedule = WebformScheduledTask::create([
      'id' => 'bar',
      'result_set_type' => 'all_submissions',
      'task_type' => 'test_task',
      'webform' => $webform->id(),
    ]);
    $unrelated_schedule->save();

    // Tasks start out un-halted.
    $this->assertFalse($schedule->isHalted());
    $this->assertFalse($unrelated_schedule->isHalted());

    // Halt with no reason.
    $schedule->halt();
    $this->assertTrue($schedule->isHalted());
    $this->assertFalse($unrelated_schedule->isHalted());
    $this->assertEquals('', $schedule->getHaltedReason());

    // Resume an ensure tasks are resumed.
    $schedule->resume();
    $this->assertFalse($schedule->isHalted());
    $this->assertFalse($unrelated_schedule->isHalted());

    // Halting with a reason will store the reason.
    $schedule->halt(t('With a reason'));
    $this->assertTrue($schedule->isHalted());
    $this->assertFalse($unrelated_schedule->isHalted());

    $this->setExpectedException(\Exception::class);
    $schedule->resume();
    $schedule->getHaltedReason();
  }

}
