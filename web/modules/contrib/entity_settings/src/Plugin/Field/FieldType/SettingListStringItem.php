<?php

namespace Drupal\entity_settings\Plugin\Field\FieldType;

use Drupal\options\Plugin\Field\FieldType\ListStringItem;

/**
 * Plugin implementation of the 'setting_list_integer' field type.
 *
 * @FieldType(
 *   id = "setting_list_string",
 *   label = @Translation("Setting List (text)"),
 *   description = @Translation("This field stores setting values from a list of allowed value|label pairs"),
 *   category = @Translation("Setting"),
 *   default_widget = "options_select",
 *   default_formatter = "list_default",
 * )
 */
class SettingListStringItem extends ListStringItem {
}
