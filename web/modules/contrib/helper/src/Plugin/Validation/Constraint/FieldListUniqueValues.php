<?php

namespace Drupal\helper\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if a field has duplicate values.
 *
 * @Constraint(
 *   id = "UniqueFieldValues",
 *   label = @Translation("Unique field values", context = "Validation"),
 * )
 */
class FieldListUniqueValues extends Constraint {

  public $message = 'The field %field_name contains duplicate values.';

  public $messageWithValues = 'The field %field_name contains duplicate values: @values.';

  /**
   * The field property to use when searching for values.
   *
   * @var string
   */
  public $property = NULL;

  /**
   * If the message should show the field values that are duplicates.
   *
   * @var bool
   */
  public $show_values = TRUE;

}
