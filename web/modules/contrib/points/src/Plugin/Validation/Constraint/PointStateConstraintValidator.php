<?php

namespace Drupal\points\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the PointState constraint.
 */
class PointStateConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    if (isset($entity)) {
      /** @var \Drupal\points\Entity\PointInterface $entity */
      if (!$entity->isNew()) {
        $state = $entity->getState();
        $saved_point = \Drupal::entityTypeManager()->getStorage('point')->loadUnchanged($entity->id());
        if ($state != $saved_point->getPoints()) {
          $this->context->addViolation($constraint->message);
        }
      }
    }
  }

}
