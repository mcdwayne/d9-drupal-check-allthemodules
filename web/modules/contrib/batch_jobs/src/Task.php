<?php

namespace Drupal\batch_jobs;

/**
 * Task class.
 */
class Task {

  /**
   * The ID of the task.
   *
   * @var int
   */
  public $tid;

  /**
   * The ID of the batch job.
   *
   * @var int
   */
  public $bid;

  /**
   * The title of the task.
   *
   * @var string
   */
  public $title;

  /**
   * The starting time stamp of the task.
   *
   * @var int
   */
  protected $start;

  /**
   * The ending time stamp of the task.
   *
   * @var int
   */
  protected $end;

  /**
   * The callbacks for the task.
   *
   * @var string
   */
  protected $callbacks;

  /**
   * The data for the task.
   *
   * @var string
   */
  protected $data;

  /**
   * The status of the task.
   *
   * @var bool
   */
  public $status;

  /**
   * The message for the task.
   *
   * @var string
   */
  public $message;

  /**
   * Task construction function.
   *
   * Creates a new task object.
   *
   * @param int $tid
   *   Task ID.
   */
  public function __construct($tid) {
    $columns = [
      'tid',
      'bid',
      'title',
      'start',
      'end',
      'callbacks',
      'data',
      'status',
      'message',
    ];
    $task = \Drupal::database()->select('batch_task', 'batch_task')
      ->condition('batch_task.tid', $tid)
      ->fields('batch_task', $columns)
      ->execute()
      ->fetchObject();

    if ($task) {
      $this->tid = $task->tid;
      $this->bid = $task->bid;
      $this->title = $task->title;
      $this->start = $task->start;
      $this->end = $task->end;
      $this->callbacks = $task->callbacks;
      $this->data = $task->data;
      $this->status = $task->status;
      $this->message = $task->message;
    }
  }

  /**
   * Check for access to the task.
   *
   * @param string $token
   *   Token string to check.
   *
   * @return bool
   *   TRUE if user has access to this task; otherwise, FALSE.
   */
  public function access($token) {
    if ($token != \Drupal::csrfToken()->get($this->tid)) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Unserialize and return data.
   *
   * @return array
   *   Array of parameters.
   */
  public function getData() {
    return unserialize($this->data);
  }

  /**
   * Unserialize and return callback functions.
   *
   * @return array
   *   Array of callback functions.
   */
  public function getCallbacks() {
    return unserialize($this->callbacks);
  }

  /**
   * Start task.
   */
  public function startTask() {
    $this->start = time();
    \Drupal::database()->update('batch_task')
      ->condition('tid', $this->tid)
      ->fields(['start' => $this->start])
      ->execute();
  }

  /**
   * Get start time.
   *
   * @return int
   *   Starting Unix time stamp.
   */
  public function getStart() {
    return $this->start;
  }

  /**
   * Get start string.
   *
   * @return string
   *   Formatted start time.
   */
  public function getStartString() {
    if ($this->start == 0) {
      return '';
    }
    return \Drupal::service('date.formatter')->format($this->start);
  }

  /**
   * End task.
   *
   * @param bool $status
   *   TRUE if task completed; otherwise, FALSE.
   * @param array $message
   *   Array of strings.
   */
  public function endTask($status, array $message = []) {
    $this->end = time();
    $this->status = $status;
    $this->message = serialize($message);
    \Drupal::database()->update('batch_task')
      ->condition('tid', $this->tid)
      ->fields([
        'end' => $this->end,
        'status' => $this->status,
        'message' => $this->message,
      ])
      ->execute();
  }

  /**
   * Get end time.
   *
   * @return int
   *   Ending Unix time stamp.
   */
  public function getEnd() {
    return $this->end;
  }

  /**
   * Get end string.
   *
   * @return string
   *   Formatted end time.
   */
  public function getEndString() {
    if ($this->end == 0) {
      return '';
    }
    return \Drupal::service('date.formatter')->format($this->end);
  }

  /**
   * Get tasks.
   *
   * @param int $bid
   *   Batcg ID.
   * @param int $number
   *   Number of tasks to retrieve.
   *
   * @return array
   *   Array of task IDs.
   */
  public static function getTasks($bid, $number) {
    $tids = \Drupal::database()->select('batch_task', 'task')
      ->condition('task.bid', $bid)
      ->condition('task.start', 0)
      ->orderBy('task.tid')
      ->range(0, $number)
      ->fields('task', ['tid'])
      ->execute()
      ->fetchCol();
    if (count($tids) > 0) {
      $now = time();
      \Drupal::database()->update('batch_task')
        ->condition('tid', $tids, 'IN')
        ->fields(['start' => -1])
        ->execute();
    }
    return $tids;
  }

}
