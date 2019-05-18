<?php

namespace Drupal\multiversion\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\workspace\WorkspaceAssociationStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
* Checks if data still exists for a deleted workspace ID.
*/
class UnpublishWorkspaceConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    /** @var \Drupal\Core\Field\FieldItemListInterface $value */
    if (!isset($value)) {
       return;
    }

    if ($value->getEntity()->isDefaultWorkspace() && !$value->value) {
      $this->context->addViolation($constraint->message);
    }

  }

}
