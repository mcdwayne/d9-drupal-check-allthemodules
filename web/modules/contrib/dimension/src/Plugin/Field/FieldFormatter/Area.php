<?php

namespace Drupal\dimension\Plugin\Field\FieldFormatter;

use Drupal\dimension\Plugin\Field\AreaTrait;

/**
 * Plugin implementation of the 'area_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "area_field_formatter",
 *   label = @Translation("Dimension: Area"),
 *   field_types = {
 *     "area_field_type"
 *   }
 * )
 */
class Area extends Dimension  {

  use AreaTrait;

}
