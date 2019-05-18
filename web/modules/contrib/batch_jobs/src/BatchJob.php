<?php

namespace Drupal\batch_jobs;

/**
 * BatchJob class.
 */
class BatchJob extends BatchJobBase {

  /**
   * BatchJob construction function.
   *
   * Creates a new batch job object with a batch ID.
   *
   * @param string $title
   *   Title of the batch job.
   * @param int $uid
   *   User ID to which the batch job belongs; 0, for accessible to all users.
   */
  public function __construct($title, $uid = 0) {
    $bid = \Drupal::database()->insert('batch_jobs')
      ->fields([
        'bid' => NULL,
        'title' => $title,
        'uid' => $uid,
      ])
      ->execute();
    $this->bid = $bid;
  }

  /**
   * Add batch parameters.
   *
   * @param array $params
   *   Array of batch parameters to pass to all tasks.
   */
  public function addBatchParams(array $params) {
    \Drupal::database()->update('batch_jobs')
      ->fields(['data' => serialize($params)])
      ->condition('bid', $this->bid)
      ->execute();
  }

  /**
   * Add callbacks to run at the end of the batch job.
   *
   * @param array $callbacks
   *   Array of functions to run at the end of batch job.
   */
  public function addBatchCallbacks(array $callbacks) {
    \Drupal::database()->update('batch_jobs')
      ->fields(['callbacks' => serialize($callbacks)])
      ->condition('bid', $this->bid)
      ->execute();
  }

  /**
   * Add task to batch job.
   *
   * @param string $title
   *   Title of the task.
   * @param string $functions
   *   Array of callbacks to run for the task.
   * @param array $params
   *   Array of parameters to pass to the task functions.
   *
   * @return int
   *   Task ID.
   */
  public function addTask($title, $functions, array $params = []) {
    $tid = \Drupal::database()->insert('batch_task')
      ->fields([
        'tid' => NULL,
        'bid' => $this->bid,
        'title' => $title,
        'callbacks' => serialize($functions),
        'data' => serialize($params),
      ])
      ->execute();
    return $tid;
  }

}
