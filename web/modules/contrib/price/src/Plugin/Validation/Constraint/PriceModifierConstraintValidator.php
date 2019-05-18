<?php

namespace Drupal\price\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
// use Drupal\price\Plugin\Field\FieldType\PriceItem;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
// use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates the price modifier constraint.
 */
class PriceModifierConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new PriceModifierConstraintValidator object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
//    if (!($value instanceof PriceItem)) {
//      throw new UnexpectedTypeException($value, PriceItem::class);
//    }

    $price_modified_item = $value;
    $modifier = $price_modified_item->get('modifier')->getValue();
    if ($modifier === NULL || $modifier === '') {
      return;
    }

    $modifiers = $this->entityTypeManager->getStorage('price_modifier')->loadMultiple();
    if (!isset($modifiers[$modifier])) {
      $this->context->buildViolation($constraint->invalidMessage)
        ->atPath('modifier')
        ->setParameter('%value', $this->formatValue($modifier))
        ->addViolation();
      return;
    }

    $available_modifiers = $constraint->availableModifiers;
    if (!empty($available_modifiers) && !in_array($modifier, $available_modifiers)) {
      $this->context->buildViolation($constraint->notAvailableMessage)
        ->atPath('modifier')
        ->setParameter('%value', $this->formatValue($modifier))
        ->addViolation();
    }
  }

}
