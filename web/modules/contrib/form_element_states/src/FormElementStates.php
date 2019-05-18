<?php

namespace Drupal\form_element_states;
/**
 * FormElementStates
 */
class FormElementStates {

  public static function settingsFrom($element,$default_value){

    $element['form_element_states'] = [
      '#type' => 'textarea',
      '#title' => t('State properties'),
      '#default_value' => $default_value,
      '#description' => t('eg:state|fieldname|option|value<br>visible|field_toogle|checked|TRUE'),
      '#weight' => 100,
    ];

    return $element;
  }

  public static function prepareStateProperties($state_values) {

    $states = [];
    $textarea_array = explode("\n", $state_values);
    foreach ($textarea_array as $textarea_row) {
      $properties = explode('|', $textarea_row);

      $option_value = strtolower($properties[3]);
      if($option_value == "true" || $option_value == "false"){
        $option_value = (bool)$option_value;
      }

      $states[$properties[0]] = [':input[name="' . $properties[1] . '"]' => ['' . $properties[2] . '' => $option_value]];
    }

    return $states;
  }
}
