<?php

namespace Drupal\random_number_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Plugin\Field\FieldWidget\NumberWidget;

/**
 * Plugin implementation of the 'random_number' widget.
 *
 * @FieldWidget(
 *   id = "random_number",
 *   label = @Translation("Number field"),
 *   field_types = {
 *     "random_integer"
 *   }
 * )
 */
class RandomNumberWidget extends NumberWidget {

}
