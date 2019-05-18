<?php

declare(strict_types = 1);

namespace Drupal\erg\Plugin\Validation\Constraint;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\erg\Event;
use Drupal\erg\Guard\GuardExceptionInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Allows entity reference guards to fail reference validation.
 */
final class OnValidateRefereeGuardConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    // Ensure this constraint is used to validate an entity reference field.
    assert($items instanceof FieldItemListInterface);
    foreach ($items as $item) {
      assert($item instanceof EntityReferenceItem);
    }
    /** @var \Drupal\Core\Field\FieldItemListInterface|\Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem[] $items */

    // Ensure this validator is used with the correct constraint.
    assert($constraint instanceof OnValidateRefereeGuardConstraint);

    // Ensure the field definition contains the required settings.
    $field_definition = $items->getFieldDefinition();
    if (!erg_field_definition_is_erg($field_definition)) {
      throw new \Exception('Missing ERG settings in field definition.');
    }

    $referee = $items->getEntity();
    $field_name = $field_definition->getName();
    try {
      erg_dispatch_for_referee_field($referee, Event::REFEREE_VALIDATE, $field_name);
    }
    catch (GuardExceptionInterface $e) {
      $this->context->addViolation($e->getMessage());
    }
  }

}
