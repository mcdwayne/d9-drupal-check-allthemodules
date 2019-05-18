<?php

namespace Drupal\changed_fields\Plugin\FieldComparator;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\PluginBase;

/**
 * @Plugin(
 *   id = "default_field_comparator"
 * )
 */
class DefaultFieldComparator extends PluginBase {

  /**
   * Method that returns comparable properties for existing field type.
   *
   * @param FieldDefinitionInterface $field_definition
   *
   * @return array
   */
  private function getComparableProperties(FieldDefinitionInterface $field_definition) {
    switch ($field_definition->getType()) {
      case 'string':
      case 'string_long':
      case 'text':
      case 'text_long':
      case 'boolean':
      case 'integer':
      case 'float':
      case 'decimal':
      case 'datetime':
      case 'email':
      case 'list_integer':
      case 'list_float':
      case 'list_string':
      case 'telephone':
        $properties = ['value'];
        break;

      case 'text_with_summary':
        $properties = [
          'value',
          'summary',
        ];
        break;

      case 'entity_reference':
        $properties = ['target_id'];
        break;

      case 'file':
        $properties = [
          'target_id',
        ];

        if ($field_definition->getSetting('description_field')) {
          $properties[] = 'description';
        }

        break;

      case 'image':
        $properties = [
          'width',
          'height',
          'target_id',
        ];

        if ($field_definition->getSetting('alt_field')) {
          $properties[] = 'alt';
        }

        if ($field_definition->getSetting('title_field')) {
          $properties[] = 'title';
        }

        break;

      case 'link':
        $properties = [
          'uri',
          'title',
        ];
        break;

      case 'daterange':
        $properties = [
          'value',
          'end_value',
        ];
        break;

      default:
        $properties = $this->getDefaultComparableProperties($field_definition);
        break;
    }

    return $this->extendComparableProperties($field_definition, $properties);
  }

  /**
   * Method that returns comparable properties for extra or custom field type.
   *
   * Use it if you want to add comparison support
   * for extra or custom field types.
   *
   * @param FieldDefinitionInterface $field_definition
   *
   * @return array
   */
  protected function getDefaultComparableProperties(FieldDefinitionInterface $field_definition) {
    return [];
  }

  /**
   * Method that returns extended comparable properties for field type.
   *
   * Use it if you want to extend comparable properties for a given field type.
   *
   * @param FieldDefinitionInterface $field_definition
   *   Array contains field instance and field base information.
   * @param array $properties
   *   Array with properties that we need to use to compare two field values.
   *
   * @return array
   *   Array with extended properties that system needs to use to compare two
   *   field values depends on core field type.
   */
  protected function extendComparableProperties(FieldDefinitionInterface $field_definition, array $properties) {
    return $properties;
  }

  /**
   * Method that compares old and new field values.
   *
   * @param FieldDefinitionInterface $field_definition
   * @param array $old_value
   * @param array $new_value
   *
   * @return array|bool
   */
  public function compareFieldValues(FieldDefinitionInterface $field_definition, array $old_value, array $new_value) {
    $result = TRUE;
    $properties = $this->getComparableProperties($field_definition);

    // If value was added or removed then we have already different values.
    if ((!$old_value && $new_value) || ($old_value && !$new_value)) {
      $result = $this->makeResultArray($old_value, $new_value);
    }
    else {
      if ($old_value && $new_value) {
        // If value was added|removed to|from multi-value field then we have
        // already different values.
        if (count($new_value) != count($old_value)) {
          $result = $this->makeResultArray($old_value, $new_value);
        }
        else {
          // Walk through each field value and compare it's properties.
          foreach ($new_value as $key => $value) {
            if (is_array($result)) {
              break;
            }

            foreach ($properties as $property) {
              if ($new_value[$key][$property] != $old_value[$key][$property]) {
                $result = $this->makeResultArray($old_value, $new_value);
                break;
              }
            }
          }
        }
      }
    }

    return $result;
  }

  /**
   * Method that generates result array for DefaultFieldComparator::compareFieldValues().
   *
   * @param array $old_value
   * @param array $new_value
   *
   * @return array
   */
  private function makeResultArray(array $old_value, array $new_value) {
    return [
      'old_value' => $old_value,
      'new_value' => $new_value,
    ];
  }

}
