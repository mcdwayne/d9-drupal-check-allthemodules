<?php

namespace Drupal\helper\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validates duplicate entity field values.
 */
class EntityFieldUniqueValuesValidator extends FieldListUniqueValuesValidator {

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $value
   * @param \Drupal\helper\Plugin\Validation\Constraint\EntityFieldUniqueValues $constraint
   */
  public function validate($value, Constraint $constraint) {
    parent::validate($value->get($constraint->field_name), $constraint);
  }

}
