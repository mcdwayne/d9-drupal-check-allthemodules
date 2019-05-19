<?php

namespace Drupal\update_runner\Event;

use Symfony\Component\EventDispatcher\Event;
use Drupal\Core\Entity\EntityInterface;

/**
 * Wraps a update job event for event listeners.
 */
class UpdateRunnerEvent extends Event {

  const UPDATE_RUNNER_JOB_CREATED = 'update_runner.job.created';
  const UPDATE_RUNNER_JOB_COMPLETED = 'update_runner.job.completed';

  /**
   * Node entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * Constructs a update runner event object.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Job being handled.
   */
  public function __construct(EntityInterface $entity) {
    $this->entity = $entity;
  }

  /**
   * Get the inserted entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Job being handled.
   */
  public function getEntity() {
    return $this->entity;
  }

}
