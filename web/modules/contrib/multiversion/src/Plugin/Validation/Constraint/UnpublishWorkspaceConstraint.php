<?php

namespace Drupal\multiversion\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Unpublish workspace constraint.
 *
 * @Constraint(
 *   id = "UnpublishWorkspace",
 *   label = @Translation("Unpublish workspace", context = "Validation"),
 * )
 */
class UnpublishWorkspaceConstraint extends Constraint {

  /**
  +   * The default violation message.
  +   *
  +   * @var string
  +   */
  public $message = 'The default workspace cannot be unpublished or archived.';

}
