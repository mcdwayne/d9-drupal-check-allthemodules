<?php

namespace Drupal\bom\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\ExecutionContextInterface;

/**
 * Checks if the item is a bom allowed item.
 *
 * @Constraint(
 *   id = "BomAllowedItem",
 *   label = @Translation("Item must be bom allowed")
 * )
 */
class BomAllowedItem extends Constraint implements ConstraintValidatorInterface {

  public $message = '@name is not a bom allowed item.';

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
    /** @var \Drupal\cbo_item\ItemInterface $item */
    $item = $items->first()->entity;
    if (!$item->get('bom_allowed')->value) {
      $this->context->addViolation($this->message, ['@name' => $item->label()]);
    }
  }

}
