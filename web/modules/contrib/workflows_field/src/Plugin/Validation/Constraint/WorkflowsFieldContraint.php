<?php

namespace Drupal\workflows_field\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint for the workflows field.
 *
 * @Constraint(
 *   id = "WorkflowsFieldConstraint",
 *   label = @Translation("WorkflowsFieldConstraint provider constraint", context = "Validation"),
 * )
 */
class WorkflowsFieldContraint extends Constraint {

  /**
   * Message displayed during an invalid transition.
   *
   * @var string
   */
  public $message = 'No transition exists to move from %previous_state to %state.';

  /**
   * Message displayed to users without appropriate permission for a given transition
   *
   * @var string
   */
  public $insufficient_permissions_transition = 'You do not have sufficient permissions to use the %transition transition.';

}
