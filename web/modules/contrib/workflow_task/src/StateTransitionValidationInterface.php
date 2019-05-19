<?php

namespace Drupal\workflow_task;

use Drupal\Core\Session\AccountInterface;
use Drupal\workflow_task\Entity\WorkflowTaskInterface;

/**
 * Validates whether a certain state transition is allowed.
 */
interface StateTransitionValidationInterface {

  /**
   * Gets a list of transitions that are legal for this user on this entity.
   *
   * @param \Drupal\workflow_task\Entity\WorkflowTaskInterface $entity
   *   The entity to be transitioned.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The account that wants to perform a transition.
   *
   * @return \Drupal\workflows\Transition[]
   *   The list of transitions that are legal for this user on this entity.
   */
  public function getValidTransitions(WorkflowTaskInterface $entity, AccountInterface $user);

}
