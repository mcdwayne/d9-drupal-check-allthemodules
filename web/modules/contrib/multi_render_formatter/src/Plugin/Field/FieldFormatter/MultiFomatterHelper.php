<?php

namespace Drupal\multi_render_formatter\Plugin\Field\FieldFormatter;

/**
 * MultiFormatterHelper Class.
 */
class MultiFomatterHelper {

  const ALLOWED_TYPES = ['boolean', 'list_float', 'list_int', 'list_string'];

  /**
   * Extract list of behaviors.
   *
   * @param string $target_entity
   *   Entity targeted.
   * @param string $target_bundle
   *   Bundle targeted.
   * @param string $target_field
   *   Behavior field selected.
   *
   * @return array|null
   *   Array of behaviors or Null.
   */
  public static function getBehaviorList($target_entity, $target_bundle, $target_field) {
    $values = NULL;
    $fields = \Drupal::entityManager()->getFieldDefinitions($target_entity, $target_bundle);

    // Check if the target field are allowed.
    if (isset($fields[$target_field]) && in_array($fields[$target_field]->getType(), self::ALLOWED_TYPES)) {
      $field_def = $fields[$target_field];
      $type = $field_def->getType();

      if ($type == 'boolean') {
        // If boolean, settings are in field Definition.
        $values = $field_def->getSettings();
      }
      else {
        // In "List*" field, list are in Storage.
        $storage = $field_def->getFieldStorageDefinition();
        $values = $storage->getSetting('allowed_values');
      }
    }
    return $values;
  }

  /**
   * Get Possible behavior selector in bundle.
   *
   * @param array $possible_fields_list
   *   List of field on the bundle.
   * @param string $target_entity
   *   Entity targeted.
   * @param string $target_bundle
   *   Bundle targeted.
   * @param string $current_field
   *   Current field.
   *
   * @return array|null
   *   Array of fields or Null.
   */
  public static function getBehaviorFieldPossible(array $possible_fields_list, $target_entity, $target_bundle, $current_field) {
    $fields = \Drupal::entityManager()->getFieldDefinitions($target_entity, $target_bundle);

    // Make field list.
    $options = [];
    foreach ($possible_fields_list as $possible_field) {

      // We only check custom fields.
      // @TODO : find how disabled "no formater fields" like "published"
      if (!strstr($possible_field, 'field_')) {
        continue;
      }

      // Unselect current field.
      if ($current_field == $possible_field) {
        continue;
      }

      // Manage only allowed fields.
      if (in_array($fields[$possible_field]->getType(), self::ALLOWED_TYPES)) {
        $options[$possible_field] = $possible_field;
      }
    }

    return $options;
  }

}
