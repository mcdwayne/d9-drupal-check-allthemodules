<?php

namespace Drupal\dimension\Plugin\Field\FieldFormatter;

use Drupal\dimension\Plugin\Field\LengthTrait;

/**
 * Plugin implementation of the 'length_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "length_field_formatter",
 *   label = @Translation("Dimension: Length"),
 *   field_types = {
 *     "length_field_type"
 *   }
 * )
 */
class Length extends Dimension  {

  use LengthTrait;

}
