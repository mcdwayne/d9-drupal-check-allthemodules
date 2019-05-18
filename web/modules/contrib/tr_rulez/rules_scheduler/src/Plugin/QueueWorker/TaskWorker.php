<?php

namespace Drupal\rules_scheduler\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Queue\RequeueException;
use Drupal\rules\Exception\EvaluationException;
use Drupal\rules_scheduler\Entity\TaskInterface;

/**
 * Queue Worker to process the Task objects waiting in the queue.
 *
 * Operates on items in the 'rules_scheduler_tasks' queue.
 * Items must be objects implementing TaskInterface in order to be
 * processed by this worker.
 *
 * @QueueWorker(
 *   id = "rules_scheduler_tasks",
 *   title = @Translation("Rules scheduler task"),
 *   cron = {"time" = 15}
 * )
 */
class TaskWorker extends QueueWorkerBase {

  /**
   * Queue worker callback for running a single task.
   *
   * @param \Drupal\rules_scheduler\Entity\TaskInterface $task
   *   The task to process.
   */
  public function processItem($task) {
    // Only objects of type TaskInterface should be in this queue - if it's
    // not a TaskInterface, we don't know how to process it.
    if ($task instanceof TaskInterface) {
      // Delegate the execution of the scheduled component to the task handler,
      // so that specialized task handlers may be used if desired. The default
      // task handler will always be DefaultTaskHanlder. Maybe this should be a
      // service that can be overridden? rules_scheduler.task_handler ....
      $handlerClass = $task->getHandler();
      if (!class_exists($handlerClass)) {
        throw new EvaluationException('Missing task handler implementation %class.', ['%class' => $handlerClass]);
        // , NULL, RulesLog::ERROR); - restore this when logging is
        // fully implemented.
      }
      $handler = new $handlerClass($task);
      $handler->runTask();

      // For testing, reschedule the same task for 2 minutes in the future,
      // without using a handler.
      // echo "retrieved task, data =" . $task->getData() . "=\n";
      // $task->setDate(time() + 2*60)->schedule();
    }
    else {
      // If we don't/can't process this task, then throw RequeueException and
      // the task will go back into the queue. Otherwise it will be deleted
      // from the queue when this function returns.
      throw new RequeueException('A task in the rules_scheduler_tasks queue does not implement TaskInterface');
    }
  }

}
