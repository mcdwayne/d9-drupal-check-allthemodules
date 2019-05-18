<?php

namespace Drupal\entity_settings;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines class for working with entity settings.
 */
class EntitySettings {

  /**
   * Custom validate handler for field_ui_field_storage_add_form.
   *
   * Changes the field prefix for entity settings fields to "setting_".
   */
  public static function validateNewSettingField(&$form, FormStateInterface $form_state) {
    $field_prefix = 'field_';
    $types = self::settingFieldTypes();

    if (in_array($form_state->getValue('new_storage_type'), $types) && $form_state->getValue('field_name')) {
      $field_name = $form_state->getValue('field_name');

      if (substr($field_name, 0, strlen($field_prefix)) == $field_prefix) {
        $field_name = 'setting_' . substr($field_name, strlen($field_prefix));
        $form_state->setValueForElement($form['new_storage_wrapper']['field_name'], $field_name);
      }
    }
  }

  /**
   * Retrieve entity settings.
   *
   * @param Drupal\Core\Entity\EntityInterface $entity
   *   The entity in which to look for settings.
   * @param bool $nested
   *   Whether or not settings from ancestor entities should be included.
   * @param array $settings
   *   The current settings. Used when $nested is true.
   * @param int $depth
   *   The current depth. Used when $nested is true.
   *
   * @return array
   *   The retrieved entity settings.
   */
  public static function getSettings(EntityInterface $entity, $nested = TRUE, array $settings = [], $depth = 0) {
    // Define aliases for the first two levels.
    switch ($depth) {
      case 0:
        $relation = 'self';
        break;

      case 1:
        $relation = 'parent';
        break;

      default:
        $relation = '';
    }

    $types = self::settingFieldTypes();
    $field_prefix = 'setting_';

    // Look at all fields on the entity.
    foreach ($entity->getFields() as $name => $field) {
      // If this is a setting field.
      if (in_array($field->getFieldDefinition()->getType(), $types)) {
        // Remove the field prefix from the name.
        if (substr($name, 0, strlen($field_prefix)) == $field_prefix) {
          $name = substr($name, strlen($field_prefix));
        }

        // Add the value to the settings.
        $settings[$depth][$name] = $field->value;

        // Add the alias, if one exists.
        if (!empty($relation)) {
          $settings[$relation][$name] = $field->value;
        }
      }
    }

    // Add any settings from ancestor entities, if appropriate.
    if ($nested && method_exists($entity, 'getParentEntity') && $parent = $entity->getParentEntity()) {
      $depth++;
      $settings = self::getSettings($parent, $nested, $settings, $depth);
    }

    return $settings;
  }

  /**
   * Define which field types are defined for settings.
   *
   * @todo Dynamically calculate this list.
   */
  public static function settingFieldTypes() {
    return [
      'setting_list_string',
      'setting_list_integer',
      'setting_boolean',
    ];
  }

}
