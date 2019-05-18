<?php

namespace Drupal\dat\Plugin\Dat\Adminer;

use Drupal\dat\DatAdminerPluginBase;

/**
 * Provides a main settings for the Database Admin Tool - Adminer.
 *
 * @DatAdminerPlugin(
 *   id = "main",
 *   name = @Translation("Adminer Main Settings"),
 *   description = @Translation("Adminer Main Settings"),
 *   weight = 0,
 *   group = "system",
 *   types = {
 *     "adminer",
 *     "editor"
 *   }
 * )
 */
class MainPlugin extends DatAdminerPluginBase {

  /**
   * Name in title and navigation.
   *
   * @return string
   *   HTML code.
   */
  public function name() {
    return \Drupal::config('dat.settings')->get('title');
  }

  /**
   * Field caption used in select and edit.
   *
   * @param array $field
   *   Single field returned from fields().
   * @param int $order
   *   Order of column in select.
   *
   * @return string
   *   HTML code, "" to ignore field.
   */
  public function fieldName($field, $order = 0) {
    if ($field['field'] == 'SSMA_TimeStamp' && $field['type'] == 'timestamp') {
      return '';
    }
    return '<span title="' . h($field["full_type"]) . '">' . h($field["field"]) . '</span>';
  }

  /**
   * Disable logout button.
   */
  public function head() {
    echo '<style>p.logout{display:none;}</style>';
  }

}
