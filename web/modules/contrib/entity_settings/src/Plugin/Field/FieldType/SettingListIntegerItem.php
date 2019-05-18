<?php

namespace Drupal\entity_settings\Plugin\Field\FieldType;

use Drupal\options\Plugin\Field\FieldType\ListIntegerItem;

/**
 * Plugin implementation of the 'setting_list_string' field type.
 *
 * @FieldType(
 *   id = "setting_list_integer",
 *   label = @Translation("Setting List (integer)"),
 *   description = @Translation("This field stores setting values from a list of allowed integer values."),
 *   category = @Translation("Setting"),
 *   default_widget = "options_select",
 *   default_formatter = "list_default",
 * )
 */
class SettingListIntegerItem extends ListIntegerItem {
}
