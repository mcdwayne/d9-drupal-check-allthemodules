<?php

namespace Drupal\Tests\webform_scheduled_tasks\Kernel;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform_scheduled_tasks\Entity\WebformScheduledTask;
use Drupal\webform_scheduled_tasks\Entity\WebformScheduledTaskInterface;

/**
 * @coversDefaultClass \Drupal\webform_scheduled_tasks\TaskRunner
 * @group webform_scheduled_tasks
 */
class TaskRunnerTest extends KernelTestBase {

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
   * A test webform.
   *
   * @var \Drupal\webform\WebformInterface
   */
  protected $testWebform;

  /**
   * The task runner.
   *
   * @var \Drupal\webform_scheduled_tasks\TaskRunnerInterface
   */
  protected $taskRunner;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('webform', ['webform']);
    $this->installEntitySchema('webform_submission');
    $this->installEntitySchema('user');

    $time = $this->prophesize(TimeInterface::class);
    $time->getRequestTime()->willReturn(1000);
    $this->container->set('datetime.time', $time->reveal());

    $this->testWebform = Webform::create([
      'id' => 'scheduled_webform',
    ]);
    $this->testWebform->save();

    $this->taskRunner = $this->container->get('webform_scheduled_tasks.task_runner');
  }

  /**
   * @covers ::getPendingTasks
   */
  public function testGetPendingTasks() {
    $expected_not_pending_tasks = [];
    $expected_pending_tasks = [];

    // A task with no interval data set, will never be pending.
    $expected_not_pending_tasks[] = $this->createTestTask();

    // A task with a run date 5 seconds in the future is not pending.
    $expected_not_pending_tasks[] = $this->createTestTask([
      'interval' => ['amount' => 1, 'multiplier' => 60],
    ])->setNextTaskRunDate(1005);

    // A task with a run date in the past, but no interval information will not
    // be a pending task. This is an invalid entity state since the run date
    // should be calculated based on the interval.
    $expected_not_pending_tasks[] = $this->createTestTask()->setNextTaskRunDate(995);

    // A task with an interval set, but no run date will default to the current
    // time + the interval, meaning it will not currently be pending.
    $expected_not_pending_tasks[] = $this->createTestTask([
      'interval' => ['amount' => 1, 'multiplier' => 60],
    ]);

    // Create a task which is ready to run, but is simple halted.
    $expected_not_pending_tasks[] = $this->createTestTask([
      'interval' => ['amount' => 1, 'multiplier' => 60],
    ])->setNextTaskRunDate(995)->halt('Something was wrong!');

    // A task with an interval and a run date that was 5 seconds in the past
    // will be pending execution.
    $expected_pending_tasks[] = $this->createTestTask([
      'interval' => ['amount' => 1, 'multiplier' => 60],
    ])->setNextTaskRunDate(995);

    $pending_tasks = $this->taskRunner->getPendingTasks();
    $id = function (WebformScheduledTaskInterface $task) {
      return $task->id();
    };

    $this->assertEquals(array_map($id, $expected_pending_tasks), array_map($id, $pending_tasks));
  }

  /**
   * Create a test task.
   *
   * @return \Drupal\webform_scheduled_tasks\Entity\WebformScheduledTaskInterface
   *   The scheduled task.
   */
  protected function createTestTask($values = []) {
    $schedule = WebformScheduledTask::create($values + [
      'id' => strtolower($this->randomMachineName()),
      'webform' => $this->testWebform->id(),
      'result_set_type' => 'all_submissions',
      'task_type' => 'test_task',
    ]);
    $schedule->save();
    return $schedule;
  }

}
