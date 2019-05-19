<?php

namespace Drupal\svg_maps\Plugin\Field\FieldType;

use Drupal\Core\Field\Plugin\Field\FieldType\DecimalItem;

/**
 * Plugin implementation of the 'svg_maps' decimal field type.
 *
 * @FieldType(
 *   id = "svg_maps_decimal",
 *   label = @Translation("Svg Map (decimal)"),
 *   description = @Translation("An entity field containing a svg map value
 *   (decimal)."), default_widget = "svg_maps", default_formatter =
 *   "svg_maps_global_decimal", category = @Translation("Svg Map"),
 * )
 */
class SvgMapsDecimalItem extends DecimalItem {

  use SvgMapsItemTrait;

}
