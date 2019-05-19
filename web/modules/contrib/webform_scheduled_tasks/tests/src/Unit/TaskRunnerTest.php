<?php

namespace Drupal\Tests\webform_scheduled_tasks\Unit;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\webform\WebformInterface;
use Drupal\webform_scheduled_tasks\Entity\WebformScheduledTaskInterface;
use Drupal\webform_scheduled_tasks\Exception\HaltScheduledTaskException;
use Drupal\webform_scheduled_tasks\Exception\RetryScheduledTaskException;
use Drupal\webform_scheduled_tasks\Plugin\WebformScheduledTasks\ResultSetPluginInterface;
use Drupal\webform_scheduled_tasks\Plugin\WebformScheduledTasks\TaskPluginInterface;
use Drupal\webform_scheduled_tasks\TaskRunner;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\webform_scheduled_tasks\TaskRunner
 * @group webform_scheduled_tasks
 */
class TaskRunnerTest extends UnitTestCase {

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
    $this->taskRunner = new TaskRunner($this->prophesize(EntityTypeManagerInterface::class)->reveal(), $this->prophesize(TimeInterface::class)->reveal());
  }

  /**
   * @covers ::executeTasks
   */
  public function testSuccessfulTaskExecuted() {
    $scheduled_task = $this->createTestScheduledTask();
    $scheduled_task->registerSuccessfulTask()->shouldBeCalled();
    $scheduled_task->incrementTaskRunDateByInterval()->shouldBeCalled();
    $this->taskRunner->executeTasks([$scheduled_task->reveal()]);
  }

  /**
   * @covers ::executeTasks
   * @dataProvider haltScheduledTaskExceptionThrownTestCases
   */
  public function testHaltScheduledTaskExceptionThrown($exception) {
    $task = $this->prophesize(TaskPluginInterface::class);
    $task->executeTask(Argument::any(), Argument::any())->willThrow($exception);

    $scheduled_task = $this->createTestScheduledTask($task);

    // When a halt schedule exception is thrown, the task will be halted and a
    // fail will be registered.
    $scheduled_task->registerFailedTask($exception)->shouldBeCalled();
    $scheduled_task->halt('An error was encountered when running the task: Failed to do something.')->shouldBeCalled();

    $this->taskRunner->executeTasks([$scheduled_task->reveal()]);
  }

  /**
   * Test cases for ::testHaltScheduledTaskExceptionThrown.
   */
  public function haltScheduledTaskExceptionThrownTestCases() {
    return [
      'Retry exception' => [
        new HaltScheduledTaskException('Failed to do something.'),
      ],
      'Normal exception' => [
        new \Exception('Failed to do something.'),
      ],
    ];
  }

  /**
   * @covers ::executeTasks
   */
  public function testRetryScheduledTaskExceptionThrown() {
    $task = $this->prophesize(TaskPluginInterface::class);
    $task->executeTask(Argument::any(), Argument::any())->willThrow(new RetryScheduledTaskException('Failed to do something.'));

    $scheduled_task = $this->createTestScheduledTask($task);

    // When a retry exception is thrown, a fail will be registered but the task
    // date will be incremented and the task will not be halted.
    $scheduled_task->registerFailedTask(Argument::any())->shouldBeCalled();
    $scheduled_task->incrementTaskRunDateByInterval()->shouldBeCalled();

    $this->taskRunner->executeTasks([$scheduled_task->reveal()]);
  }

  /**
   * @covers ::executeTasks
   */
  public function testMultipleTasksRun() {
    $failing_task = $this->prophesize(TaskPluginInterface::class);
    $failing_task->executeTask(Argument::any(), Argument::any())->willThrow(new \Exception('Failed!'));
    $failing_scheduled_task = $this->createTestScheduledTask($failing_task);
    $failing_scheduled_task->registerFailedTask(Argument::any())->shouldBeCalled();
    $failing_scheduled_task->halt(Argument::any())->shouldBeCalled();

    $passing_scheduled_task = $this->createTestScheduledTask();
    $passing_scheduled_task->registerSuccessfulTask()->shouldBeCalled();
    $passing_scheduled_task->incrementTaskRunDateByInterval()->shouldBeCalled();

    // One failed task should not impact the running of another.
    $this->taskRunner->executeTasks([$failing_scheduled_task->reveal(), $passing_scheduled_task->reveal()]);
  }

  /**
   * Create a mock scheduled task to pass into the task runner.
   *
   * @param \Drupal\search_api\Task\TaskInterface|null $task
   *   (Optional) A mock task or NULL if a default one should be setup.
   * @param \Drupal\webform_scheduled_tasks\Plugin\WebformScheduledTasks\ResultSetPluginInterface|null $result_set
   *   (Optional) A mock result set or NULL if a default one should be setup.
   *
   * @return \Drupal\webform_scheduled_tasks\Entity\WebformScheduledTaskInterface
   *   A scheduled task entity.
   */
  protected function createTestScheduledTask($task = NULL, $result_set = NULL) {
    $iterator = new \ArrayIterator([]);
    $webform = $this->prophesize(WebformInterface::class);

    if ($result_set === NULL) {
      $result_set = $this->prophesize(ResultSetPluginInterface::class);
      $result_set->getResultSet()->willReturn($iterator);
    }
    if ($task === NULL) {
      $task = $this->prophesize(TaskPluginInterface::class);
      $task->executeTask($iterator)->shouldBeCalled();
    }

    $scheduled_task = $this->prophesize(WebformScheduledTaskInterface::class);
    $scheduled_task->getTaskPlugin()->willReturn($task->reveal());
    $scheduled_task->getResultSetPlugin()->willReturn($result_set->reveal());
    $scheduled_task->getWebform()->willReturn($webform->reveal());

    return $scheduled_task;
  }

}
