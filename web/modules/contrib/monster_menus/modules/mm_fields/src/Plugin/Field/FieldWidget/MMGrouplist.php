<?php
/**
 * @file
 * Contains \Drupal\mm_fields\Plugin\Field\FieldWidget\MMGrouplist.
 */

namespace Drupal\mm_fields\Plugin\Field\FieldWidget;

/**
 * @FieldWidget(
 *  id = "mm_grouplist",
 *  label = @Translation("MM Group chooser"),
 *  description = @Translation("Lets the user choose one or more MM groups."),
 *  field_types = {"mm_grouplist"},
 *  multiple_values = TRUE
 * )
 */
class MMGrouplist extends MMFieldWidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'mm_list_popup_start' => '',
      ] + parent::defaultSettings();
  }

}
