<?php

namespace Drupal\bigint\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Plugin\Field\FieldWidget\NumberWidget;

/**
 * Plugin implementation of the 'bigint' widget.
 *
 * @FieldWidget(
 *   id = "bigint",
 *   label = @Translation("Number field (bigint)"),
 *   field_types = {
 *     "bigint",
 *   }
 * )
 */
class BigIntItemWidget extends NumberWidget {

}
