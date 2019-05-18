<?php

namespace Drupal\cached_computed_field\Plugin\Field\FieldType;

use Drupal\text\Plugin\Field\FieldType\TextLongItem;

/**
 * Plugin implementation of the cached computed long text field.
 *
 * @FieldType(
 *   id = "cached_computed_text_long",
 *   label = @Translation("Text (formatted, long)"),
 *   description = @Translation("This field caches lengthy computed textual data in normal field storage."),
 *   category = @Translation("Cached computed field"),
 *   default_widget = "text_textarea",
 *   default_formatter = "text_default"
 * )
 */
class CachedComputedTextLongItem extends TextLongItem {

  use CachedComputedItemTrait;

}
