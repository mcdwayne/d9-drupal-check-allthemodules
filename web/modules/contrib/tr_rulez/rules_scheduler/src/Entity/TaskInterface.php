<?php

namespace Drupal\rules_scheduler\Entity;

/**
 * Provides an interface that defines the Task class.
 *
 * Tasks are created in memory using Task::create().
 * Tasks are 'scheduled' by saving them in the database using Task::schedule().
 * Tasks are retrieved from the database using Task::load().
 * Scheduled tasks will be queued for execution at the time returned by
 *   Task::getDate(). When a Task has been executed it will be deleted
 *   automatically, or you can delete it using Task::delete(). The execution
 *   and deletion of scheduled tasks is handled by the Drupal Queue API.
 * Task::loadReadyToRun() searches scheduled tasks and returns an array of
 *   Tasks which are ready to execute, based on the scheduled datetime and the
 *   current datetime.
 */
interface TaskInterface {

  /**
   * Returns a unique sequence number for tasks.
   *
   * @return int
   *   The task sequence number.
   */
  public function id();

  /**
   * Sets the task identifier to the given value.
   *
   * @param string $identifier
   *   The task identifier.
   *
   * @return $this
   */
  public function setIdentifier($identifier);

  /**
   * Returns the identifier of this task.
   *
   * @return string
   *   The task identifier.
   */
  public function getIdentifier();

  /**
   * Sets the scheduled time timestamp of this task.
   *
   * @param int $date
   *   The name of this order status.
   *
   * @return $this
   */
  public function setDate($date);

  /**
   * Returns the scheduled time timestamp of this task.
   *
   * @return int
   *   The scheduled time of this status.
   */
  public function getDate();

  /**
   * Sets the name of the task component to the given value.
   *
   * @param string $config
   *   The name of the task component.
   *
   * @return $this
   */
  public function setConfig($config);

  /**
   * Returns the name of the task component.
   *
   * @return string
   *   The name of the task component.
   */
  public function getConfig();

  /**
   * Sets any additional data to store with the task.
   *
   * @param object $data
   *   Object holding the additional data.
   *
   * @return $this
   */
  public function setData($data);

  /**
   * Returns any additional data to store with the task.
   *
   * @return object
   *   Object holding the additional data.
   */
  public function getData();

  /**
   * Sets the task handler class name.
   *
   * @param string $handler
   *   The fully-qualified name of the task handler class.
   *
   * @return $this
   */
  public function setHandler($handler);

  /**
   * Returns the task handler class name.
   *
   * @return string
   *   The fully-qualified name of the task handler class.
   */
  public function getHandler();

  /**
   * Creates a Task, but does not schedule it.
   *
   * @param array $values
   *   (optional) Array of initialization values.
   *
   * @return \Drupal\rules_scheduler\Entity\TaskInterface
   *   An object implementing TaskInterface.
   */
  public static function create(array $values);

  /**
   * Loads a scheduled task.
   *
   * @param int $tid
   *   A task unique sequence number.
   *
   * @return \Drupal\rules_scheduler\Entity\TaskInterface|null
   *   A task object, or NULL if there isn't one.
   */
  public static function load($tid);

  /**
   * Loads all tasks that are ready to execute.
   *
   * "Ready to run" means that the 'now' datetime is greater than or equal to
   * the task's scheduled time.
   *
   * @return \Drupal\rules_scheduler\Entity\TaskInterface[]
   *   An array of task objects.
   */
  public static function loadReadyToRun();

  /**
   * Schedules this task to be executed later on.
   */
  public function schedule();

  /**
   * Deletes the task.
   */
  public function delete();

}
