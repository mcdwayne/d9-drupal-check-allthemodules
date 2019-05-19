<?php

namespace Drupal\webform_scheduled_tasks;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\webform_scheduled_tasks\Entity\WebformScheduledTaskInterface;
use Drupal\webform_scheduled_tasks\Exception\HaltScheduledTaskException;
use Drupal\webform_scheduled_tasks\Exception\RetryScheduledTaskException;

/**
 * A task runner used for executing scheduled tasks.
 */
class TaskRunner implements TaskRunnerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * TaskRunner constructor.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, TimeInterface $time) {
    $this->entityTypeManager = $entityTypeManager;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public function executeTasks(array $scheduled_tasks) {
    foreach ($scheduled_tasks as $scheduled_task) {

      $result_set = $scheduled_task->getResultSetPlugin();
      $task = $scheduled_task->getTaskPlugin();

      try {
        $task->executeTask($result_set->getResultSet());
        $scheduled_task->registerSuccessfulTask();
        $scheduled_task->incrementTaskRunDateByInterval();
      }
      catch (HaltScheduledTaskException $e) {
        // Catch and exception which should halt running the task entirely.
        $scheduled_task->registerFailedTask($e);
        $scheduled_task->halt(sprintf('An error was encountered when running the task: %s', $e->getMessage()));
      }
      catch (RetryScheduledTaskException $e) {
        // Catch an exception type which has a high chance of success if the
        // task is simply run again.
        $scheduled_task->registerFailedTask($e);
        $scheduled_task->incrementTaskRunDateByInterval();
      }
      catch (\Exception $e) {
        // By default, unless a retry exception is thrown, halt the task and
        // add an error message.
        $scheduled_task->registerFailedTask($e);
        $scheduled_task->halt(sprintf('An error was encountered when running the task: %s', $e->getMessage()));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPendingTasks() {
    // A task is only considered "scheduled" when the actual interval details
    // have been set.
    $storage = $this->entityTypeManager->getStorage('webform_scheduled_task');
    $query = $storage->getQuery();
    $query->exists('interval.amount');
    $query->exists('interval.multiplier');

    // Pending tasks are ones where time has exceeded the next run date.
    return array_values(array_filter($storage->loadMultiple($query->execute()), function (WebformScheduledTaskInterface $scheduled_task) {
      return !$scheduled_task->isHalted() && $scheduled_task->getNextTaskRunDate() < $this->time->getRequestTime();
    }));
  }

}
