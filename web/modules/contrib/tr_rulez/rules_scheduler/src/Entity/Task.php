<?php

namespace Drupal\rules_scheduler\Entity;

/**
 * Defines the Task class.
 */
class Task implements TaskInterface {

  /**
   * Unique identifier for tasks.
   *
   * @var int
   */
  protected $tid;

  /**
   * User-provided string to identify the task per scheduled configuration.
   *
   * @var string
   */
  protected $identifier;

  /**
   * Timestamp when the component should be executed.
   *
   * @var int
   */
  protected $date;

  /**
   * The machine readable name of the to-be-scheduled component.
   *
   * @var string
   */
  protected $config;

  /**
   * Any additional data to store with the task.
   *
   * @var object
   */
  protected $data;

  /**
   * The name of the task handler class.
   *
   * @var string
   */
  protected $handler;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->tid;
  }

  /**
   * {@inheritdoc}
   */
  public function setIdentifier($identifier) {
    $this->identifier = $identifier;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getIdentifier() {
    return $this->identifier;
  }

  /**
   * {@inheritdoc}
   */
  public function setDate($date) {
    $this->date = $date;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDate() {
    return $this->date;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfig($config) {
    $this->config = $config;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig() {
    return $this->config;
  }

  /**
   * {@inheritdoc}
   */
  public function setData($data) {
    $this->data = $data;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getData() {
    return $this->data;
  }

  /**
   * {@inheritdoc}
   */
  public function setHandler($handler) {
    $this->handler = $handler;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getHandler() {
    return $this->handler;
  }

  /**
   * Protected constructor. Tasks may not be instantiated via 'new'.
   */
  protected function __construct() {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(array $values = NULL) {
    $task = new Task();
    if (isset($values)) {
      foreach ($values as $key => $value) {
        $task->$key = $value;
      }
    }
    return $task;
  }

  /**
   * {@inheritdoc}
   */
  public static function load($tid) {
    $result = \Drupal::database()->query('SELECT * FROM {rules_scheduler} WHERE tid = :id', [':id' => $tid]);
    if ($assoc = $result->fetchAssoc()) {
      if (isset($assoc['data'])) {
        $assoc['data'] = unserialize($assoc['data']);
      }
      $task = Task::create($assoc);
    }
    else {
      return NULL;
    }

    return $task;
  }

  /**
   * {@inheritdoc}
   */
  public static function loadReadyToRun() {
    $time = \Drupal::time()->getRequestTime();

    // Fetch all Tasks that have a timestamp less than or equal to 'now'.
    $result = \Drupal::database()->select('rules_scheduler', 'r')
      ->fields('r', ['tid'])
      ->condition('date', $time, '<=')
      ->orderBy('date')
      ->execute();

    $task = [];
    foreach ($result as $record) {
      $task[] = Task::load($record->tid);
    }

    return $task;
  }

  /**
   * {@inheritdoc}
   */
  public function schedule() {
    // If there is a task with the same identifier and component,
    // we replace it. This allows us to use identifiers unique to a
    // user/node/etc. For example, to send a user a notification 10
    // days after last login - if we have the uid in the identifier,
    // we can have the same notification component scheduled once per
    // user, and we automatically cancel/re-schedule the notification
    // if the user logs in again.
    if (!empty($this->identifier)) {
      \Drupal::database()->delete('rules_scheduler')
        ->condition('config', $this->config)
        ->condition('identifier', $this->identifier)
        ->execute();
    }
    \Drupal::database()->merge('rules_scheduler')
      ->condition('config', $this->config)
      ->condition('identifier', $this->identifier)
      ->fields([
        'identifier' => $this->identifier,
        'date' => $this->date,
        'config' => $this->config,
        'data' => serialize($this->data),
        'handler' => $this->handler,
      ])
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    \Drupal::database()->delete('rules_scheduler')
      ->condition('tid', $this->tid)
      ->execute();
  }

}
