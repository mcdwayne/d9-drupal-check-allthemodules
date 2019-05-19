<?php

namespace Drupal\workflow_task\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Verifies that nodes have a valid task state.
 *
 * @Constraint(
 *   id = "TaskState",
 *   label = @Translation("Valid task state", context = "Validation")
 * )
 */
class TaskStateConstraint extends Constraint {

  public $message = 'Invalid state transition from %from to %to';
  public $invalidStateMessage = 'State %state does not exist on %workflow workflow';

}
