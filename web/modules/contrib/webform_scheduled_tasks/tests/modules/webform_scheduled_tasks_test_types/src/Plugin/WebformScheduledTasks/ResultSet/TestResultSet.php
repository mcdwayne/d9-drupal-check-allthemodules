<?php

namespace Drupal\webform_scheduled_tasks_test_types\Plugin\WebformScheduledTasks\ResultSet;

use Drupal\webform_scheduled_tasks\Plugin\WebformScheduledTasks\ResultSetPluginBase;

/**
 * A test result set.
 *
 * @WebformScheduledResultSet(
 *   id = "test_result_set",
 *   label = @Translation("Test result set"),
 * )
 */
class TestResultSet extends ResultSetPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getResultSet() {
    return new \ArrayIterator([]);
  }

  /**
   * {@inheritdoc}
   */
  public function onSuccess() {
    \Drupal::messenger()->addStatus('Run test_result_set ::onSuccess');
  }

  /**
   * {@inheritdoc}
   */
  public function onFailure() {
    \Drupal::messenger()->addStatus('Run test_result_set ::onFailure');
  }

}
