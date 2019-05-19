<?php

namespace Drupal\Tests\webform_scheduled_tasks\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\Traits\Core\CronRunTrait;

/**
 * An end to end integration test for creating and running a task.
 *
 * @group webform_scheduled_tasks
 */
class ScheduledTaskRunTest extends BrowserTestBase {

  use CronRunTrait;

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
   * Test creating and running a task.
   */
  public function testTaskRun() {
    $this->drupalGet('admin/structure/webform/manage/contact/scheduled-tasks');
    $this->clickLink('Add scheduled task');
    $this->submitForm([
      'label' => 'Test task',
      'id' => 'test_task',
      'task_type' => 'test_task',
      'result_set_type' => 'all_submissions',
    ], 'Save');
    $this->submitForm([
      'interval[amount]' => 12,
      'interval[multiplier]' => 86400,
      // Test the next run date into the past, to ensure this gets executed
      // immediately.
      'next_run[date]' => '2005-07-15',
      'next_run[time]' => '20:41:35',
    ], 'Save');

    // Create three form submissions.
    foreach (range(1, 3) as $i) {
      $this->drupalPostForm('webform/contact', [
        'subject' => 'Test submission',
        'message' => 'Test message',
      ], 'Send message');
    }

    // Run cron and visit the homepage to see all messages from the test
    // plugins.
    $this->cronRun();
    $this->drupalGet('<front>');

    $this->assertSession()->pageTextContains('Run test_task ::executeTask');
    $this->assertSession()->pageTextContains('Processed submission 1');
    $this->assertSession()->pageTextContains('Processed submission 2');
    $this->assertSession()->pageTextContains('Processed submission 3');
    $this->assertSession()->pageTextContains('Run test_task ::onSuccess');
  }

}
