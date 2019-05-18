<?php

namespace Drupal\clockpicker\Element;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides our custom #process callback for the datetime form element.
 *
 * @see hook_element_info_alter()
 * @see \Drupal\Core\Datetime\Element\Datetime
 */
class Datetime {

  /**
   * Adds clockpicker functionality.
   *
   * This expands on the possible values for the '#date_time_element' attribute,
   * allowing a value of 'clockpicker'.
   */
  public static function processClockpicker(&$element, FormStateInterface $form_state, &$complete_form) {
    if ($element['#date_time_element'] == 'clockpicker') {
      $element['#attached']['library'][] = 'clockpicker/clockpicker';

      $element['time']['#attributes']['class'][] = 'clockpicker';
    }

    return $element;
  }

}
