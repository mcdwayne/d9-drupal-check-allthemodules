<?php

namespace Drupal\entity_settings\Plugin\Field\FieldType;

use Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem;

/**
 * Defines the 'setting boolean' entity field type.
 *
 * @FieldType(
 *   id = "setting_boolean",
 *   label = @Translation("Setting Boolean"),
 *   description = @Translation("An entity setting field containing a boolean value."),
 *   category = @Translation("Setting"),
 *   default_widget = "boolean_checkbox",
 *   default_formatter = "boolean",
 * )
 */
class SettingBooleanItem extends BooleanItem {
}
