<?php

/**
 * @file
 * Contains DefaultFieldComparator.php.
 */

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
   * @param FieldDefinitionInterface $fieldDefinition
   *
   * @return array
   */
  private function getComparableProperties(FieldDefinitionInterface $fieldDefinition) {
    switch ($fieldDefinition->getType()) {
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

        if ($fieldDefinition->getSetting('description_field')) {
          $properties[] = 'description';
        }

        break;

      case 'image':
        $properties = [
          'fid',
          'width',
          'height',
          'target_id',
        ];

        if ($fieldDefinition->getSetting('alt_field')) {
          $properties[] = 'alt';
        }

        if ($fieldDefinition->getSetting('title_field')) {
          $properties[] = 'title';
        }

        break;

      case 'link':
        $properties = [
          'uri',
          'title',
        ];
        break;

      default:
        $properties = $this->getDefaultComparableProperties($fieldDefinition);
        break;
    }

    return $this->extendComparableProperties($fieldDefinition, $properties);
  }

  /**
   * Method that returns comparable properties for extra or custom field type.
   *
   * Use it if you want to add comparison support
   * for extra or custom field types.
   *
   * @param FieldDefinitionInterface $fieldDefinition
   *
   * @return array
   */
  protected function getDefaultComparableProperties(FieldDefinitionInterface $fieldDefinition) {
    return [];
  }

  /**
   * Method that returns extended comparable properties for field type.
   *
   * Use it if you want to extend comparable properties for a given field type.
   *
   * @param FieldDefinitionInterface $fieldDefinition
   *   Array contains field instance and field base information.
   * @param array $properties
   *   Array with properties that we need to use to compare two field values.
   *
   * @return array
   *   Array with extended properties that system needs to use to compare two
   *   field values depends on core field type.
   */
  protected function extendComparableProperties(FieldDefinitionInterface $fieldDefinition, array $properties) {
    return $properties;
  }

  /**
   * Method that compares old and new field values.
   *
   * @param FieldDefinitionInterface $fieldDefinition
   * @param array $oldValue
   * @param array $newValue
   *
   * @return array|bool
   */
  public function compareFieldValues(FieldDefinitionInterface $fieldDefinition, array $oldValue, array $newValue) {
    $result = TRUE;
    $properties = $this->getComparableProperties($fieldDefinition);

    // If value was added or removed then we have already different values.
    if ((!$oldValue && $newValue) || ($oldValue && !$newValue)) {
      $result = $this->makeResultArray($oldValue, $newValue);
    }
    else {
      if ($oldValue && $newValue) {
        // If value was added|removed to|from multi-value field then we have
        // already different values.
        if (count($newValue) != count($oldValue)) {
          $result = $this->makeResultArray($oldValue, $newValue);
        }
        else {
          // Walk through each field value and compare it's properties.
          foreach ($newValue as $key => $value) {
            if (is_array($result)) {
              break;
            }

            foreach ($properties as $property) {
              if ($newValue[$key][$property] != $oldValue[$key][$property]) {
                $result = $this->makeResultArray($oldValue, $newValue);
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
   * @param array $oldValue
   * @param array $newValue
   *
   * @return array
   */
  private function makeResultArray(array $oldValue, array $newValue) {
    return [
      'old_value' => $oldValue,
      'new_value' => $newValue,
    ];
  }

}
