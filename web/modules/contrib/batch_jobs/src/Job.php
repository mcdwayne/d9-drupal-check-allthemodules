<?php

namespace Drupal\batch_jobs;

use Drupal\user\Entity\User;

/**
 * Job class.
 */
class Job extends BatchJobBase {

  /**
   * The title of the batch job.
   *
   * @var string
   */
  public $title;

  /**
   * The owner ID of the batch job.
   *
   * @var int
   */
  protected $uid;

  /**
   * The data for the batch job.
   *
   * @var string
   */
  protected $data;

  /**
   * The callbacks for the batch job.
   *
   * @var string
   */
  protected $callbacks;

  /**
   * The status of the batch job.
   *
   * @var bool
   */
  public $status;

  /**
   * Job construction function.
   *
   * Creates a new job object.
   *
   * @param int $bid
   *   Batch ID.
   */
  public function __construct($bid) {
    $columns = [
      'bid',
      'title',
      'uid',
      'data',
      'callbacks',
      'status',
    ];
    $job = \Drupal::database()->select('batch_jobs', 'jobs')
      ->condition('jobs.bid', $bid)
      ->fields('jobs', $columns)
      ->execute()
      ->fetchObject();
    if ($job) {
      $this->bid = $bid;
      $this->title = $job->title;
      $this->uid = $job->uid;
      $this->data = $job->data;
      $this->callbacks = $job->callbacks;
      $this->status = $job->status;
    }
  }

  /**
   * Check for access to the job.
   *
   * @param string $token
   *   Token string to check.
   *
   * @return bool
   *   TRUE if user has access to this job; otherwise, FALSE.
   */
  public function access($token = NULL) {
    if (!is_null($token)) {
      if ($token != $this->getToken($this->bid)) {
        return FALSE;
      }
    }
    $user = \Drupal::currentUser();
    if ($user->id() != 1 && $this->uid != 0 && $user->id() != $this->uid) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Get user name.
   *
   * @return string
   *   User name.
   */
  public function getUser() {
    $user = User::load($this->uid);
    return $user->label();
  }

  /**
   * Unserialize and return data.
   */
  public function getData() {
    return unserialize($this->data);
  }

  /**
   * Unserialize and return callbacks.
   */
  public function getCallbacks() {
    return unserialize($this->callbacks);
  }

  /**
   * Run batch (finish) tasks.
   *
   * @return bool
   *   TRUE if tasks ran; otherwise, FALSE.
   */
  public function finish() {
    // Check if job has already completed.
    if ($this->status) {
      return FALSE;
    }

    foreach ($this->getCallbacks() as $callback) {
      call_user_function($callback, $this->getData());
    }
    \Drupal::database()->update('batch_jobs')
      ->condition('bid', $this->bid)
      ->fields(['status' => 1])
      ->execute();

    return TRUE;
  }

  /**
   * Delete job.
   *
   * @param int $bid
   *   Batch ID.
   */
  public static function delete($bid) {
    \Drupal::database()->delete('batch_jobs')
      ->condition('bid', $bid)
      ->execute();
    \Drupal::database()->delete('batch_task')
      ->condition('bid', $bid)
      ->execute();
  }

  /**
   * Get total number of tasks.
   *
   * @return int
   *   Total number of tasks.
   */
  public function total() {
    $total = \Drupal::database()->select('batch_task', 'task')
      ->condition('task.bid', $this->bid)
      ->countQuery()
      ->execute()
      ->fetchField();
    return $total;
  }

  /**
   * Get the number of started tasks.
   *
   * @return int
   *   Number of tasks started.
   */
  public function started() {
    $started = \Drupal::database()->select('batch_task', 'task')
      ->condition('task.bid', $this->bid)
      ->condition('task.start', 0, '>')
      ->countQuery()
      ->execute()
      ->fetchField();
    return $started;
  }

  /**
   * Get the number of completed tasks.
   *
   * @return int
   *   Number of completed tasks.
   */
  public function completed() {
    $completed = \Drupal::database()->select('batch_task', 'task')
      ->condition('task.bid', $this->bid)
      ->condition('task.end', 0, '!=')
      ->countQuery()
      ->execute()
      ->fetchField();
    return $completed;
  }

  /**
   * Get number of errors.
   *
   * @return int
   *   Total number of errors.
   */
  public function errors() {
    $total = \Drupal::database()->select('batch_task', 'task')
      ->condition('task.bid', $this->bid)
      ->condition('task.status', 0)
      ->condition('task.end', 0, '>')
      ->countQuery()
      ->execute()
      ->fetchField();
    return $total;
  }

}
