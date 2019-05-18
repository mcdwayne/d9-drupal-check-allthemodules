<?php
/**
 * @file
 * Contains \Drupal\mm_fields\Plugin\Field\FieldWidget\MMNodelist.
 */

namespace Drupal\mm_fields\Plugin\Field\FieldWidget;

use Drupal\monster_menus\Constants;

/**
 * @FieldWidget(
 *  id = "mm_nodelist",
 *  label = @Translation("MM Node chooser"),
 *  description = @Translation("Lets the user choose one or more MM nodes."),
 *  field_types = {"mm_nodelist"},
 *  multiple_values = TRUE
 * )
 */
class MMNodelist extends MMFieldWidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'mm_list_popup_start' => '',
        'mm_list_enabled' => Constants::MM_PERMS_READ,
        'mm_list_selectable' => Constants::MM_PERMS_APPLY,
        'mm_list_nodetypes' => [],
      ] + parent::defaultSettings();
  }

}
