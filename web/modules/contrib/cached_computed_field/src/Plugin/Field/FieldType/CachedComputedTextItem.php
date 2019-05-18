<?php

namespace Drupal\cached_computed_field\Plugin\Field\FieldType;

use Drupal\text\Plugin\Field\FieldType\TextItem;

/**
 * Plugin implementation of the cached computed text field.
 *
 * @FieldType(
 *   id = "cached_computed_text",
 *   label = @Translation("Text (formatted)"),
 *   description = @Translation("This field caches computed textual data in normal field storage."),
 *   category = @Translation("Cached computed field"),
 *   default_widget = "text_textfield",
 *   default_formatter = "text_default"
 * )
 */
class CachedComputedTextItem extends TextItem {

  use CachedComputedItemTrait;

}
