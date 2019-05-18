<?php

namespace Drupal\cbo_organization\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\ExecutionContextInterface;

/**
 * Checks if the organization's parent is validate.
 *
 * @Constraint(
 *   id = "OrganizationParent",
 *   label = @Translation("Organization parent validate")
 * )
 */
class OrganizationParentConstraint extends Constraint implements ConstraintValidatorInterface {

  /**
   * @var \Symfony\Component\Validator\ExecutionContextInterface
   */
  protected $context;

  /**
   * {@inheritdoc}
   */
  public function initialize(ExecutionContextInterface $context) {
    $this->context = $context;
  }

  /**
   * {@inheritdoc}
   */
  public function validatedBy() {
    return get_class($this);
  }

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    if (!$item = $items->first()) {
      return;
    }

    $entity = $items->getEntity();
    if ($id = $entity->id()) {
      if ($item->value == $id) {
        $this->context->addViolation('Parent organization could not be refer to itself.');
      }
      else {
        $children = \Drupal::entityTypeManager()->getStorage('organization')
          ->loadAllChildren($entity->id());
        if (in_array($item->value, array_keys($children))) {
          $this->context->addViolation("Parent organization could not be refer to it's children.");
        }
      }
    }
  }

}
