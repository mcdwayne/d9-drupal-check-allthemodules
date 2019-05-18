<?php

namespace Drupal\cached_computed_field\Plugin\Field\FieldType;

use Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem;

/**
 * Plugin implementation of the cached computed boolean field.
 *
 * @FieldType(
 *   id = "cached_computed_boolean",
 *   label = @Translation("Boolean"),
 *   description = @Translation("This field caches computed boolean data in normal field storage."),
 *   category = @Translation("Cached computed field"),
 *   default_widget = "boolean_checkbox",
 *   default_formatter = "boolean",
 * )
 */
class CachedComputedBooleanItem extends BooleanItem {

  use CachedComputedItemTrait;

}
