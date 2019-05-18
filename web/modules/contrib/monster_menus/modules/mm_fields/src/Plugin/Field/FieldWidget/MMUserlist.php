<?php
/**
 * @file
 * Contains \Drupal\mm_fields\Plugin\Field\FieldWidget\MMUserlist.
 */

namespace Drupal\mm_fields\Plugin\Field\FieldWidget;

/**
 * @FieldWidget(
 *  id = "mm_userlist",
 *  label = @Translation("MM User chooser"),
 *  description = @Translation("Lets the user choose one or more MM users."),
 *  field_types = {"mm_userlist"},
 *  multiple_values = TRUE
 * )
 */
class MMUserlist extends MMFieldWidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return ['mm_list_show_info' => FALSE] + parent::defaultSettings();
  }

}
