<?php

namespace Drupal\dimension\Plugin\Field\FieldWidget;

use Drupal\dimension\Plugin\Field\LengthTrait;

/**
 * Plugin implementation of the 'length_field_widget' widget.
 *
 * @FieldWidget(
 *   id = "length_field_widget",
 *   label = @Translation("Dimension: Length field"),
 *   field_types = {
 *     "length_field_type"
 *   }
 * )
 */
class Length extends Dimension {

  use LengthTrait;

  /**
   * @inheritdoc
   */
  public static function defaultSettings() {
    return self::_defaultSettings(self::fields());
  }

}
