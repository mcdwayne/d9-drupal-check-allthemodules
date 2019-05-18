<?php

namespace Drupal\cached_computed_field\Plugin\Field\FieldType;

use Drupal\Core\Field\Plugin\Field\FieldType\StringItem;

/**
 * Plugin implementation of the cached computed string field.
 *
 * @FieldType(
 *   id = "cached_computed_string",
 *   label = @Translation("Text (plain)"),
 *   description = @Translation("This field caches computed string data in normal field storage."),
 *   category = @Translation("Cached computed field"),
 *   default_widget = "string_textfield",
 *   default_formatter = "string"
 * )
 */
class CachedComputedStringItem extends StringItem {

  use CachedComputedItemTrait;

}
