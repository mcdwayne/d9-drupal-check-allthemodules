<?php

namespace Drupal\cbo_inventory\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\ExecutionContextInterface;

/**
 * Checks whether the item is stockable.
 *
 * @Constraint(
 *   id = "StockableItem",
 *   label = @Translation("Item must be stockable")
 * )
 */
class StockableItem extends Constraint implements ConstraintValidatorInterface {

  public $message = '@name is not stockable.';

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
    if ($item = $items->first()->entity) {
      if (!$item->get('stockable')->value) {
        $this->context->addViolation($this->message, ['@name' => $item->label()]);
      }
    }
  }

}
