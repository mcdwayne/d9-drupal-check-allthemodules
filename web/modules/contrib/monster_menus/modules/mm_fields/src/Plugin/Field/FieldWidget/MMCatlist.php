<?php
/**
 * @file
 * Contains \Drupal\mm_fields\Plugin\Field\FieldWidget\MMCatlist.
 */

namespace Drupal\mm_fields\Plugin\Field\FieldWidget;

use Drupal\monster_menus\Constants;

/**
 * @FieldWidget(
 *  id = "mm_catlist",
 *  label = @Translation("MM Page chooser"),
 *  description = @Translation("Lets the user choose one or more MM pages."),
 *  field_types = {"mm_catlist"},
 *  multiple_values = TRUE
 * )
 */
class MMCatlist extends MMFieldWidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'mm_list_popup_start' => '',
        'mm_list_enabled' => Constants::MM_PERMS_READ,
        'mm_list_selectable' => Constants::MM_PERMS_APPLY,
      ] + parent::defaultSettings();
  }

}
