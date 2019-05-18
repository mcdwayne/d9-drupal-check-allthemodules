<?php

namespace Drupal\dimension\Plugin\Field\FieldWidget;

use Drupal\dimension\Plugin\Field\VolumeTrait;

/**
 * Plugin implementation of the 'volume_field_widget' widget.
 *
 * @FieldWidget(
 *   id = "volume_field_widget",
 *   label = @Translation("Dimension: Volume field"),
 *   field_types = {
 *     "volume_field_type"
 *   }
 * )
 */
class Volume extends Dimension {

  use VolumeTrait;

  /**
   * @inheritdoc
   */
  public static function defaultSettings() {
    return self::_defaultSettings(self::fields());
  }

}
