<?php

namespace Drupal\workflow_task;

use Drupal\Core\Session\AccountInterface;
use Drupal\workflow_task\Entity\WorkflowTaskInterface;
use Drupal\workflows\Transition;

/**
 * Validates whether a certain state transition is allowed.
 */
class StateTransitionValidation implements StateTransitionValidationInterface {

  /**
   * Stores the possible state transitions.
   *
   * @var array
   */
  protected $possibleTransitions = [];

  /**
   * Constructs a new StateTransitionValidation.
   */
  public function __construct() {
  }

  /**
   * {@inheritdoc}
   */
  public function getValidTransitions(WorkflowTaskInterface $entity, AccountInterface $user) {
    $workflow = $entity->getWorkflow();
    $current_state = $entity->getStateId() ? $workflow->getTypePlugin()->getState($entity->getStateId()) : $workflow->getTypePlugin()->getInitialState($entity);

    return array_filter($current_state->getTransitions(), function (Transition $transition) use ($workflow, $user) {
      return $user->hasPermission('use ' . $workflow->id() . ' transition ' . $transition->id());
    });
  }

}
