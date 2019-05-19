<?php

namespace Drupal\webform_scheduled_tasks;

use Drupal\Core\State\StateInterface;
use Drupal\webform_scheduled_tasks\Entity\WebformScheduledTaskInterface;

/**
 * Get information about the state of task scheduling.
 *
 * Information related to the scheduling of a task such as the next time a task
 * is due to be run or if the task has been stopped due to error are stored in
 * state instead of configuration. This is so tasks can be deployed alongside
 * webforms, as per a typical configuration management workflow and a those
 * config values wil never be updated automatically by the scheduling system.
 *
 * This ensures that the scheduling system operates according to the rules of
 * if a task is halted or not and the upcoming scheduling intervals, regardless
 * of deploys and config imports.
 */
class SchedulingState implements SchedulingStateInterface {

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The key used in state for storing scheduling information.
   */
  const SCHEDULE_STATE_KEY = 'webform_scheduled_tasks.scheduling_info';

  /**
   * A state key for halted tasks.
   */
  const HALTED_STATE_KEY = 'webform_scheduled_tasks.halted_tasks';

  /**
   * SchedulingState constructor.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function hasTimeScheduled(WebformScheduledTaskInterface $scheduledTask) {
    $scheduled_times = $this->state->get(static::SCHEDULE_STATE_KEY);
    return isset($scheduled_times[$scheduledTask->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getNextScheduledTime(WebformScheduledTaskInterface $scheduledTask) {
    $scheduled_times = $this->state->get(static::SCHEDULE_STATE_KEY);
    return isset($scheduled_times[$scheduledTask->id()]) ? $scheduled_times[$scheduledTask->id()] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setNextScheduledTime(WebformScheduledTaskInterface $scheduledTask, $timestamp) {
    $scheduled_times = $this->state->get(static::SCHEDULE_STATE_KEY);
    $scheduled_times[$scheduledTask->id()] = $timestamp;
    $this->state->set(static::SCHEDULE_STATE_KEY, $scheduled_times);
  }

  /**
   * {@inheritdoc}
   */
  public function isHalted(WebformScheduledTaskInterface $scheduledTask) {
    $halted_tasks = $this->state->get(static::HALTED_STATE_KEY);
    return isset($halted_tasks[$scheduledTask->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getHaltedMessage(WebformScheduledTaskInterface $scheduledTask) {
    if (!$this->isHalted($scheduledTask)) {
      throw new \Exception('Tried to get the halted task message from a task that is not halted.');
    }
    $halted_tasks = $this->state->get(static::HALTED_STATE_KEY);
    return $halted_tasks[$scheduledTask->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function haltTask(WebformScheduledTaskInterface $scheduledTask, $message = '') {
    $halted_tasks = $this->state->get(static::HALTED_STATE_KEY);
    $halted_tasks[$scheduledTask->id()] = $message;
    $this->state->set(static::HALTED_STATE_KEY, $halted_tasks);
  }

  /**
   * {@inheritdoc}
   */
  public function resumeTask(WebformScheduledTaskInterface $scheduledTask) {
    $halted_tasks = $this->state->get(static::HALTED_STATE_KEY);
    unset($halted_tasks[$scheduledTask->id()]);
    $this->state->set(static::HALTED_STATE_KEY, $halted_tasks);
  }

}
