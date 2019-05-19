<?php

namespace Drupal\svg_maps\Plugin\Field\FieldType;

use Drupal\Core\Field\Plugin\Field\FieldType\IntegerItem;

/**
 * Plugin implementation of the 'svg_maps' integer field type.
 *
 * @FieldType(
 *   id = "svg_maps_integer",
 *   label = @Translation("Svg Map (integer)"),
 *   description = @Translation("An entity field containing a svg map value
 *   (integer)."), default_widget = "svg_maps", default_formatter =
 *   "svg_maps_global_integer", category = @Translation("Svg Map"),
 * )
 */
class SvgMapsIntegerItem extends IntegerItem {

  use SvgMapsItemTrait;

}
