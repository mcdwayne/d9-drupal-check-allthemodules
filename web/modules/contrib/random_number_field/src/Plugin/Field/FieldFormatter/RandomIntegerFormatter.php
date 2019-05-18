<?php

namespace Drupal\random_number_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\IntegerFormatter;

/**
 * Plugin implementation of the 'random_number_integer' formatter.
 *
 * The 'Default' formatter is different for integer fields on the one hand, and
 * for decimal and float fields on the other hand, in order to be able to use
 * different settings.
 *
 * @FieldFormatter(
 *   id = "random_number_integer",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "random_integer"
 *   }
 * )
 */
class RandomIntegerFormatter extends IntegerFormatter {

}
