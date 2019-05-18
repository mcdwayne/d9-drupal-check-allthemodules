<?php

namespace Drupal\cached_computed_field\Plugin\Field\FieldType;

use Drupal\Core\Field\Plugin\Field\FieldType\DecimalItem;

/**
 * Plugin implementation of the cached computed decimal field.
 *
 * @FieldType(
 *   id = "cached_computed_decimal",
 *   label = @Translation("Number (decimal)"),
 *   description = @Translation("This field caches computed decimal data in normal field storage."),
 *   category = @Translation("Cached computed field"),
 *   default_widget = "number",
 *   default_formatter = "number_decimal"
 * )
 */
class CachedComputedDecimalItem extends DecimalItem {

  use CachedComputedItemTrait;

}
