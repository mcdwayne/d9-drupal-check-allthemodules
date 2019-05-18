<?php

namespace Drupal\rules_scheduler;

use Drupal\rules_scheduler\Entity\TaskInterface;

/**
 * Default scheduled task handler.
 */
class DefaultTaskHandler implements TaskHandlerInterface {

  /**
   * The task.
   *
   * @var \Drupal\rules_scheduler\Entity\TaskInterface
   */
  protected $task;

  /**
   * Constructs a repetitive task handler object.
   */
  public function __construct(TaskInterface $task) {
    $this->task = $task;
  }

  /**
   * {@inheritdoc}
   */
  public function runTask() {
    // Get the execution state from the task.
    $data = $this->task->getData();

    /* @todo Implement! */
  }

  /**
   * {@inheritdoc}
   */
  public function afterTaskQueued() {
    // Delete the task from the task list.
    $this->task->delete();
  }

  /**
   * {@inheritdoc}
   */
  public function getTask() {
    return $this->task;
  }

}
