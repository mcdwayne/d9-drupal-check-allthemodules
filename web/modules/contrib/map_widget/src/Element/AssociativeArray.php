<?php

namespace Drupal\map_widget\Element;

use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class KeyValuePair provides a form element for entering paired values.
 *
 * @package Drupal\map_widget\Render\Element
 * @author Shawn P. Duncan <code@sd.shawnduncan.org>
 *
 * Copyright 2019 by Shawn P. Duncan.  This code is
 * released under the GNU General Public License.
 * Which means that it is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or (at
 * your option) any later version.
 * http://www.gnu.org/licenses/gpl.html
 *
 * Properties:
 * - #count: The number textfield pairs to build for input.
 * - #size: The size of the input element in characters.
 * - #key_placeholder: The splaceholder on the key textfield.
 * - #value_placeholder: The placeholder on the value textfield.
 *
 * Usage example:
 * @code
 * $form['map'] = [
 *    '#type' => 'map_associative',
 *    '#value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
 *    '#key_placeholder' => $this->getSetting('key_placeholder'),
 *    '#value_placeholder' => $this->getSetting('value_placeholder'),
 *    '#size' => $this->getSetting('size'),
 * ];
 * @endcode
 *
 * @FormElement("map_associative")
 */
class AssociativeArray extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#count' => 1,
      '#size' => 60,
      '#key_placeholder' => NULL,
      '#value_placeholder' => NULL,
      '#process' => [
        [$class, 'processAssociativeArray'],
      ],
      '#theme_wrappers' => ['fieldset'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(
    &$element,
    $input,
    FormStateInterface $form_state
  ) {
    if (is_array($input)) {
      $value = [];
      foreach ($input as $item) {
        if (!empty($item['key'])) {
          $value[$item['key']] = $item['value'];
        }
      }
      return $value;
    }
    if (!isset($element['#default_value'])) {
      $element['#default_value'] = [];
    }
    return [];
  }

  /**
   * Form API callback: Processes an associative array form element.
   *
   * This method is assigned as a #process callback in getInfo() method.
   */
  public static function processAssociativeArray(
    &$element,
    FormStateInterface $form_state,
    &$complete_form
  ) {
    $elementIndex = 0;
    if (empty($element['#default_value'])) {
      // One empty pair if there is no value.
      $element[$elementIndex] = self::arrayElementForm(
        '',
        '',
        $element['#size'],
        $element['#key_placeholder'],
        $element['#value_placeholder'],
        $element['#required']
      );
    }
    foreach ($element['#default_value'] as $key => $value) {
      // Each key/value pair in it's own mini form.
      $element[$elementIndex] = self::arrayElementForm(
        $key,
        $value,
        $element['#size'],
        $element['#key_placeholder'],
        $element['#value_placeholder'],
        $element['#required']
      );
      $elementIndex++;
    }
    for ($extra = $elementIndex; $extra < $element['#count']; $extra++) {
      // Add extra empty pairs if the count is more than then number of pairs.
      $element[$extra] = self::arrayElementForm(
        '',
        '',
        $element['#size'],
        $element['#key_placeholder'],
        $element['#value_placeholder'],
        FALSE
      );
    }
    return $element;
  }

  /**
   * Helper function for building a single key/value pair form.
   *
   * This method is used in a Form API callback and so needs to be static.
   *
   * @param string $key
   *   The key.
   * @param string $value
   *   The value.
   * @param int $size
   *   The field size in characters.
   * @param string $keyPlaceholder
   *   The placeholder for the key field.
   * @param string $valuePlaceholder
   *   The placeholder for the value field.
   * @param bool $required
   *   Is the field required.
   *
   * @return array
   *   The render array for the key/value pair.
   */
  public static function arrayElementForm(
    $key,
    $value,
    $size,
    $keyPlaceholder,
    $valuePlaceholder,
    $required
  ) {
    return [
      '#type' => 'container',
      '#attributes' => ['class' => ['map-associative-element']],
      '#attached' => [
        'library' => ['map_widget/associative_element'],
      ],
      'key' => [
        '#type' => 'textfield',
        '#default_value' => $key,
        '#size' => $size,
        '#placeholder' => $keyPlaceholder,
        '#required' => $required,
        '#attributes' => [
          'class' => ['map-associative--key'],
        ],
      ],
      'value' => [
        '#type' => 'textfield',
        '#default_value' => $value,
        '#size' => $size,
        '#placeholder' => $valuePlaceholder,
        '#required' => $required,
        '#attributes' => [
          'class' => ['map-associative--value'],
        ],
      ],
    ];
  }

}
