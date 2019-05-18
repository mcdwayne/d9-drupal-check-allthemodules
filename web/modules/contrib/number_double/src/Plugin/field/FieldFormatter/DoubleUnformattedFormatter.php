<?php

namespace Drupal\number_double\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\NumericUnformattedFormatter;

/**
 * Plugin implementation of the 'number_double' unformatted formatter.
 *
 * @FieldFormatter(
 *   id = "number_double_unformatted",
 *   label = @Translation("Unformatted"),
 *   field_types = {
 *     "double"
 *   }
 * )
 */
class DoubleUnformattedFormatter extends NumericUnformattedFormatter {

}
