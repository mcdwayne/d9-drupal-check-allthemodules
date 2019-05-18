<?php
/**
 * @file
 * Contains \Drupal\monster_menus\Element\MMHelpRadios.
 */

namespace Drupal\monster_menus\Element;

use Drupal\Core\Render\Element\Radios;

/**
 * Provides a list of radio buttons with accompanying help text.
 *
 * @FormElement("mm_help_radios")
 */
class MMHelpRadios extends Radios {

  public function getInfo() {
    $info = parent::getInfo();
    $info['#process'][] = [get_class($this), 'process'];
    $info['#help'] = [];
    return $info;
  }

  /**
   * Expand the element into multiple rows.
   *
   * @param array $element
   *   The form element to process.
   * @return array
   *   The form element.
   */
  public static function process($element) {
    if ($element['#options']) {
      foreach ($element['#options'] as $key => $choice) {
        if (isset($element[$key])) {
          // We set the #type to radio so that the correct classes get applied
          // to the outer container, but turn the label off so that it can be
          // output using our #theme.
          $element[$key]['#type'] = 'radio';
          $element[$key]['#title_display'] = 'none';
          $element[$key]['#theme'] = 'mm_help_radio';
          $element[$key]['#help'] = $element['#help'][$key];
        }
      }
    }
    return $element;
  }

}