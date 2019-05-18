<?php

namespace Drupal\points\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint for the entity changed timestamp.
 *
 * @Constraint(
 *   id = "PointState",
 *   label = @Translation("Point state", context = "Validation"),
 *   type = {"entity:point"}
 * )
 */
class PointStateConstraint extends Constraint {

  public $message = 'The point has either been modified by another user, or you have already submitted modifications. As a result, your changes cannot be saved.';

}
