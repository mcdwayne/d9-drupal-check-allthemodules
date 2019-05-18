<?php

namespace Drupal\cached_computed_field\Plugin\Field\FieldType;

use Drupal\Core\Field\Plugin\Field\FieldType\StringLongItem;

/**
 * Plugin implementation of the cached computed long string field.
 *
 * @FieldType(
 *   id = "cached_computed_string_long",
 *   label = @Translation("Text (plain, long)"),
 *   description = @Translation("This field caches lengthy computed string data in normal field storage."),
 *   category = @Translation("Cached computed field"),
 *   default_widget = "string_textarea",
 *   default_formatter = "basic_string",
 * )
 */
class CachedComputedStringLongItem extends StringLongItem {

  use CachedComputedItemTrait;

}
