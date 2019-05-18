<?php

/**
 * @file
 * Contains ExtendedFieldComparator.php.
 */

namespace Drupal\changed_fields_extended_field_comparator\Plugin\FieldComparator;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\changed_fields\Plugin\FieldComparator\DefaultFieldComparator;

/**
 * @Plugin(
 *   id = "extended_field_comparator"
 * )
 */
class ExtendedFieldComparator extends DefaultFieldComparator {

  /**
   * {@inheritdoc}
   */
  public function getDefaultComparableProperties(FieldDefinitionInterface $fieldDefinition) {
    $properties = [];

    // Return comparable field properties for extra or custom field type.
    if ($fieldDefinition->getType() == 'some_field_type') {
      $properties = [
        'some_field_property_1',
        'some_field_property_2',
      ];
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function extendComparableProperties(FieldDefinitionInterface $fieldDefinition, array $properties) {
    // Return extended field properties for a given field type based on
    // field definition.
    if ($fieldDefinition->getType() == 'some_field_type') {
      $properties[] = 'some_field_property_3';
    }

    return $properties;
  }

}
