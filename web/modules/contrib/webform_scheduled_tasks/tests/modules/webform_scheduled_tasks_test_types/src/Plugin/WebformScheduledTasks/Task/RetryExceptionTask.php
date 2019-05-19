<?php

namespace Drupal\webform_scheduled_tasks_test_types\Plugin\WebformScheduledTasks\Task;

use Drupal\webform_scheduled_tasks\Exception\RetryScheduledTaskException;
use Drupal\webform_scheduled_tasks\Plugin\WebformScheduledTasks\TaskPluginBase;

/**
 * A test plugin that throws an exception.
 *
 * @WebformScheduledTask(
 *   id = "retry_exception",
 *   label = @Translation("Retry exception"),
 * )
 */
class RetryExceptionTask extends TaskPluginBase {

  /**
   * {@inheritdoc}
   */
  public function executeTask(\Iterator $submissions) {
    \Drupal::messenger()->addStatus('Attempted to run retry_exception.');
    throw new RetryScheduledTaskException('Something was temporarily wrong.');
  }

  /**
   * {@inheritdoc}
   */
  public function onSuccess() {
    \Drupal::messenger()->addStatus('Run retry_exception ::onSuccess');
  }

  /**
   * {@inheritdoc}
   */
  public function onFailure() {
    \Drupal::messenger()->addStatus('Run retry_exception ::onFailure');
  }

}
