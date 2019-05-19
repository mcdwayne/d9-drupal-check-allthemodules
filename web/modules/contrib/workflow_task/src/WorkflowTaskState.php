<?php

namespace Drupal\workflow_task;

use Drupal\workflows\StateInterface;

/**
 * A value object representing a workflow state for content moderation.
 */
class WorkflowTaskState implements StateInterface {

  /**
   * The vanilla state object from the Workflow module.
   *
   * @var \Drupal\workflows\StateInterface
   */
  protected $state;

  /**
   * If entities should be published if in this state.
   *
   * @var bool
   */
  protected $published;

  /**
   * If entities should be the default revision if in this state.
   *
   * @var bool
   */
  protected $defaultRevision;

  /**
   * WorkflowTaskState constructor.
   *
   * Decorates state objects to add methods to determine if an entity should be
   * published or made the default revision.
   *
   * @param \Drupal\workflows\StateInterface $state
   *   The vanilla state object from the Workflow module.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->state->id();
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->state->label();
  }

  /**
   * {@inheritdoc}
   */
  public function weight() {
    return $this->state->weight();
  }

  /**
   * {@inheritdoc}
   */
  public function canTransitionTo($to_state_id) {
    return $this->state->canTransitionTo($to_state_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getTransitionTo($to_state_id) {
    return $this->state->getTransitionTo($to_state_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getTransitions() {
    return $this->state->getTransitions();
  }

}
