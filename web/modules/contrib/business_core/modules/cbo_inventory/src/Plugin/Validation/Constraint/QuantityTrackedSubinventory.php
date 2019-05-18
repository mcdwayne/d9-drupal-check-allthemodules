<?php

namespace Drupal\cbo_inventory\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\ExecutionContextInterface;

/**
 * Checks whether the subinventory doing quantity track.
 *
 * @Constraint(
 *   id = "QuantityTrackedSubinventory",
 *   label = @Translation("Subinventory must be quantity tracked")
 * )
 */
class QuantityTrackedSubinventory extends Constraint implements ConstraintValidatorInterface {

  public $message = '@name is not quantity tracked.';

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
    /** @var \Drupal\Core\Field\FieldItemListInterface $items */
    if ($subinventory = $items->first()->entity) {
      if (!$subinventory->get('quantity_tracked')->value) {
        $this->context->addViolation($this->message, ['@name' => $subinventory->label()]);
      }
    }
  }

}
