<?php

namespace Drupal\cached_computed_field\Plugin\Field\FieldType;

use Drupal\Core\Field\Plugin\Field\FieldType\FloatItem;

/**
 * Plugin implementation of the cached computed floating point field.
 *
 * @FieldType(
 *   id = "cached_computed_float",
 *   label = @Translation("Number (float)"),
 *   description = @Translation("This field caches computed floating point data in normal field storage."),
 *   category = @Translation("Cached computed field"),
 *   default_widget = "number",
 *   default_formatter = "number_decimal"
 * )
 */
class CachedComputedFloatItem extends FloatItem {

  use CachedComputedItemTrait;

}
