<?php

namespace Drupal\dimension\Plugin\Field\FieldWidget;

use Drupal\dimension\Plugin\Field\AreaTrait;

/**
 * Plugin implementation of the 'area_field_widget' widget.
 *
 * @FieldWidget(
 *   id = "area_field_widget",
 *   label = @Translation("Dimension: Area field"),
 *   field_types = {
 *     "area_field_type"
 *   }
 * )
 */
class Area extends Dimension {

  use AreaTrait;

  /**
   * @inheritdoc
   */
  public static function defaultSettings() {
    return self::_defaultSettings(self::fields());
  }

}
