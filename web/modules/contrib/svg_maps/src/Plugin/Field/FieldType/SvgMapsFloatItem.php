<?php

namespace Drupal\svg_maps\Plugin\Field\FieldType;

use Drupal\Core\Field\Plugin\Field\FieldType\FloatItem;

/**
 * Plugin implementation of the 'svg_maps' float field type.
 *
 * @FieldType(
 *   id = "svg_maps_float",
 *   label = @Translation("Svg Map (float)"),
 *   description = @Translation("An entity field containing a svg map value
 *   (float)."), default_widget = "svg_maps", default_formatter =
 *   "svg_maps_global_decimal", category = @Translation("Svg Map"),
 * )
 */
class SvgMapsFloatItem extends FloatItem {

  use SvgMapsItemTrait;

}
