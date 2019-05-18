<?php

namespace Drupal\cached_computed_field\Plugin\Field\FieldType;

use Drupal\Core\Field\Plugin\Field\FieldType\IntegerItem;

/**
 * Plugin implementation of the cached computed integer field.
 *
 * @FieldType(
 *   id = "cached_computed_integer",
 *   label = @Translation("Integer"),
 *   description = @Translation("This field caches computed integer data in normal field storage."),
 *   category = @Translation("Cached computed field"),
 *   default_widget = "number",
 *   default_formatter = "number_integer"
 * )
 */
class CachedComputedIntegerItem extends IntegerItem {

  use CachedComputedItemTrait;

}
