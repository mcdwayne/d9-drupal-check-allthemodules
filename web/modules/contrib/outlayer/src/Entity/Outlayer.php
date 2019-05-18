<?php

namespace Drupal\outlayer\Entity;

use Drupal\gridstack\Entity\GridStackBase;

/**
 * Defines the Outlayer configuration entity.
 *
 * @ConfigEntityType(
 *   id = "outlayer",
 *   label = @Translation("Outlayer optionset"),
 *   list_path = "admin/structure/outlayer",
 *   config_prefix = "optionset",
 *   entity_keys = {
 *     "id" = "name",
 *     "label" = "label",
 *     "status" = "status",
 *     "weight" = "weight",
 *   },
 *   config_export = {
 *     "id",
 *     "name",
 *     "label",
 *     "status",
 *     "weight",
 *     "options",
 *     "json",
 *   }
 * )
 */
class Outlayer extends GridStackBase implements OutlayerInterface {

  /**
   * Massages options.
   */
  public static function massageOptions(array &$js) {
    $layout = $js['layoutMode'];

    // @todo $defaults = self::defaultSettings();
    // @todo $js = array_diff_assoc($js, $defaults);
    // Unlike masonry, packery doesn't need columnWidth.
    if ($layout == 'packery') {
      unset($js['layout']['columnWidth']);
    }
    if ($layout != 'masonry') {
      unset($js['layout']['horizontalOrder']);
    }

    // Overrides the layout key with the actual name of the layoutMode.
    if (!empty($js['layout'])) {
      $js[$layout] = $js['layout'];
      unset($js['layout']);
    }

    // The fitColumns does not have any option.
    if ($layout == 'fitColumns') {
      $js = ['layoutMode' => 'fitColumns'];
    }
  }

}
