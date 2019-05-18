<?php

namespace Drupal\helper\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if an entity field has duplicate values.
 *
 * @Constraint(
 *   id = "HelperEntityFieldUniqueValues",
 *   label = @Translation("Unique field values", context = "Validation"),
 *   type = {"entity"},
 * )
 */
class EntityFieldUniqueValues extends FieldListUniqueValues {

  /**
   * The field name to validate.
   *
   * @var string
   */
  public $field_name;

  /**
   * {@inheritdoc}
   */
  public function getDefaultOption() {
    return 'field_name';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredOptions() {
    return ['field_name'];
  }

}
