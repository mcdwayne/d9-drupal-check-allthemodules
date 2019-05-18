<?php

namespace Drupal\hexidecimal_color\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\StringData;

/**
 * Provides the Hexidecimal Color typed data type.
 *
 * This data type is a wrapper for hexidecimal color strings, in the format
 * #XXX or #XXXXXX, where X is a hexidecimal character (0-9, a-f).
 *
 * @DataType(
 *   id = "hexidecimal_color",
 *   label = @Translation("Hexidecimal Color"),
 * )
 */
class HexColorData extends StringData implements HexColorInterface {

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE) {
    // Force uppercase strings for consistency.
    $value = strtoupper($value);

    parent::setValue($value, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
    $constraints = parent::getConstraints();
    // Add a constraint to ensure that submitted data are hexidecimal color
    // strings.
    $constraints[] = $constraint_manager->create('hexidecimal_color', []);

    return $constraints;
  }

}
