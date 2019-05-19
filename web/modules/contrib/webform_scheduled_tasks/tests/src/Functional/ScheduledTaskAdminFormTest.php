<?php

namespace Drupal\Tests\webform_scheduled_tasks\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\webform_scheduled_tasks\Entity\WebformScheduledTask;

/**
 * Test the scheduled tasks admin form.
 *
 * @group webform_scheduled_tasks
 */
class ScheduledTaskAdminFormTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'block',
    'webform_scheduled_tasks',
    'webform_scheduled_tasks_test_types',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('local_actions_block');

    $this->drupalLogin($this->drupalCreateUser([
      'administer webform',
    ]));
  }

  /**
   * Test the admin UI.
   */
  public function testAdminUi() {
    $this->drupalGet('admin/structure/webform/manage/contact/scheduled-tasks');
    $this->clickLink('Add scheduled task');

    // Step one is setitng the task type and result type.
    $this->submitForm([
      'label' => 'Foo',
      'id' => 'foo',
      'task_type' => 'test_task',
      'result_set_type' => 'all_submissions',
    ], 'Save');
    $this->assertSession()->pageTextContains('The scheduled task was saved successfully.');

    // Step two is saving the entity.
    $this->submitForm([
      'interval[amount]' => 12,
      'interval[multiplier]' => 86400,
      'task_settings[test_option]' => TRUE,
    ], 'Save');
    $this->assertSession()->pageTextContains('The scheduled task was saved successfully.');

    // Assert the next run date was automatically assigned a time in the future
    // equal to the interval amount * the interval multiplier, with a 5 second
    // delta, to account for some variance between the form submit time and the
    // time the entity was loaded.
    $schedule = WebformScheduledTask::load('foo');
    $this->assertEquals(time() + 1036800, $schedule->getNextTaskRunDate(), '', 5);
  }

  /**
   * Test the UI when specifying a manual time to schedule the next run.
   */
  public function testAdminUiManualScheduledRun() {
    $this->drupalGet('admin/structure/webform/manage/contact/scheduled-tasks');
    $this->clickLink('Add scheduled task');
    $this->submitForm([
      'label' => 'Foo',
      'id' => 'foo',
      'task_type' => 'test_task',
      'result_set_type' => 'all_submissions',
    ], 'Save');
    $this->assertSession()->pageTextContains('The scheduled task was saved successfully.');

    $this->submitForm([
      'interval[amount]' => 12,
      'interval[multiplier]' => 86400,
      'next_run[date]' => '2021-07-15',
      'next_run[time]' => '20:41:35',
    ], 'Save');
    $this->assertSession()->pageTextContains('The scheduled task was saved successfully.');

    // The next run date should be fixed to the time set manually by the user.
    $schedule = WebformScheduledTask::load('foo');
    $this->assertEquals(1626345695, $schedule->getNextTaskRunDate());
  }

  /**
   * Test the admin controls for resuming a halted schedule.
   */
  public function testResumeHaltedSchedule() {
    $task = WebformScheduledTask::create([
      'id' => 'foo',
      'task_type' => 'test_task',
      'result_set_type' => 'all_submissions',
      'label' => 'Test task',
      'webform' => 'contact',
      'interval' => [
        'amount' => 12,
        'multiplier' => 86400,
      ],
    ]);
    $task->save();

    $this->drupalGet("admin/structure/webform/manage/contact/scheduled-tasks/{$task->id()}/edit");
    $this->assertSession()->pageTextContains('Active');

    $task->halt('Something went really wrong.');
    $this->drupalGet("admin/structure/webform/manage/contact/scheduled-tasks/{$task->id()}/edit");
    $this->assertSession()->pageTextContains('Halted');
    $this->assertSession()->pageTextContains('Something went really wrong.');

    $this->submitForm([], 'Resume task');
    $this->assertSession()->pageTextContains('The scheduled task was resumed and will run during the next scheduled interval.');

    $schedule = WebformScheduledTask::load('foo');
    $this->assertFalse($schedule->isHalted());
    $this->assertEquals(time() + 1036800, $schedule->getNextTaskRunDate(), '', 5);
  }

}
