<?php

namespace Drupal\cpf\Plugin\Validation\Constraint;

use Drupal\Component\Utility\Unicode;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates that a field is unique for the given entity type.
 */
class CpfUniqueConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {

    $fieldDefinition = $constraint->fieldDefinition;
    $field_name = $fieldDefinition->getName();
    $entity = $constraint->entity;
    $entity_type_id = $entity->getEntityTypeId();
    $id_key = $entity->getEntityType()->getKey('id');
    $id = (int) $entity->id();

    $query = \Drupal::entityQuery($entity_type_id)
      ->condition($id_key, $id, '<>')
      ->condition($field_name, $value);

    if (!$constraint->ignoreBundle) {
      $query->condition('type', $entity->bundle());
    }

    $value_taken = (bool) $query->range(0, 1)
      ->count()
      ->execute();

    if ($value_taken) {
      $this->context->addViolation($constraint->message, [
        '%value' => $value,
        '@entity_type' => $entity->getEntityType()->getLowercaseLabel(),
        '@field_name' => Unicode::strtolower($fieldDefinition->getLabel()),
      ]);
    }
  }

}
