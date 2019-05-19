<?php

namespace Drupal\Tests\webform_scheduled_tasks\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform_scheduled_tasks\Entity\WebformScheduledTask;

/**
 * Test the scheduled tasks list builder.
 *
 * @group webform_scheduled_tasks
 */
class ScheduledTaskListBuilderTest extends BrowserTestBase {

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
   * Test the list builder filtered by the ID of the webform.
   */
  public function testFilteredListBuilder() {
    $schedule_webform = Webform::create([
      'id' => 'scheduled_webform',
    ]);
    $schedule_webform->save();
    $schedule = WebformScheduledTask::create([
      'id' => 'test_task',
      'label' => 'Test schedule',
      'webform' => $schedule_webform->id(),
      'result_set_type' => 'all_submissions',
      'task_type' => 'test_task',
    ]);
    $schedule->save();

    $unrelated_webform = Webform::create([
      'id' => 'unrelated_form',
    ]);
    $unrelated_webform->save();
    $unrelated_schedule = WebformScheduledTask::create([
      'id' => 'unrelated_schedule',
      'label' => 'Unrelated schedule',
      'webform' => $unrelated_webform->id(),
      'result_set_type' => 'all_submissions',
      'task_type' => 'test_task',
    ]);
    $unrelated_schedule->save();

    $this->drupalGet('admin/structure/webform/manage/scheduled_webform/scheduled-tasks');
    $this->assertSession()->pageTextContains('Test schedule');
    $this->assertSession()->pageTextNotContains('Unrelated schedule');

    $this->drupalGet('admin/structure/webform/manage/unrelated_form/scheduled-tasks');
    $this->assertSession()->pageTextNotContains('Test schedule');
    $this->assertSession()->pageTextContains('Unrelated schedule');
  }

}
