<?php

namespace Drupal\webform_scheduled_tasks_test_types\Plugin\WebformScheduledTasks\Task;

use Drupal\webform_scheduled_tasks\Exception\HaltScheduledTaskException;
use Drupal\webform_scheduled_tasks\Plugin\WebformScheduledTasks\TaskPluginBase;

/**
 * A test plugin that throws an exception.
 *
 * @WebformScheduledTask(
 *   id = "halt_exception_task",
 *   label = @Translation("Retry exception"),
 * )
 */
class HaltExceptionTask extends TaskPluginBase {

  /**
   * {@inheritdoc}
   */
  public function executeTask(\Iterator $submissions) {
    \Drupal::messenger()->addStatus('Run halt_exception_task ::executeTask');
    throw new HaltScheduledTaskException('Something went terribly wrong.');
  }

  /**
   * {@inheritdoc}
   */
  public function onSuccess() {
    \Drupal::messenger()->addStatus('Run halt_exception_task ::onSuccess');
  }

  /**
   * {@inheritdoc}
   */
  public function onFailure() {
    \Drupal::messenger()->addStatus('Run halt_exception_task ::onFailure');
  }

}
