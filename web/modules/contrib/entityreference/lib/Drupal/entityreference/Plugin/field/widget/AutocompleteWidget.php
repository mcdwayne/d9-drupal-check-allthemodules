<?php

/**
 * @file
 * Definition of Drupal\entityreference\Plugin\field\widget\AutocompleteWidget.
 */

namespace Drupal\entityreference\Plugin\field\widget;

use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\field\Plugin\Type\Widget\WidgetBase;

use Drupal\entityreference\Plugin\field\widget\AutocompleteWidgetBase;

/**
 * Plugin implementation of the 'entityreference autocomplete' widget.
 *
 * @todo: Check if the following statement is still correct
 * The autocomplete path doesn't have a default here, because it's not the
 * the two widgets, and the Field API doesn't update default settings when
 * the widget changes.
 *
 * @Plugin(
 *   id = "entityreference_autocomplete",
 *   module = "entityreference",
 *   label = @Translation("Autocomplete"),
 *   description = @Translation("An autocomplete text field."),
 *   field_types = {
 *     "entityreference"
 *   },
 *   settings = {
 *     "match_operator" = "CONTAINS",
 *     "size" = 60,
 *     "path" = ""
 *   }
 * )
 */
class AutocompleteWidget extends AutocompleteWidgetBase {

  /**
   * Implements Drupal\field\Plugin\Type\Widget\WidgetInterface::formElement().
   */
  public function formElement(array $items, $delta, array $element, $langcode, array &$form, array &$form_state) {
    // We let the Field API handles multiple values for us, only take
    // care of the one matching our delta.
    if (isset($items[$delta])) {
      $items = array($items[$delta]);
    }
    else {
      $items = array();
    }

    $element = $this->prepareElement($items, $delta, $element, $langcode, $form, $form_state, 'entityreference/autocomplete/single');
    return array('target_id' => $element);
  }

  /**
   * Implements Drupal\entityreference\Plugin\field\widget\DefaultAutocompleteWidget::elementValidate()
   */
  public function elementValidate($element, &$form_state) {
    // If a value was entered into the autocomplete.
    $value = '';
    if (!empty($element['#value'])) {
      // Take "label (entity id)', match the id from parenthesis.
      if (preg_match("/.+\((\d+)\)/", $element['#value'], $matches)) {
        $value = $matches[1];
      }
      else {
        // Try to get a match from the input string when the user didn't use the
        // autocomplete but filled in a value manually.
        $field = field_info_field($element['#field_name']);
        $instance = field_info_instance($element['#entity_type'], $element['#field_name'], $element['#bundle']);
        $handler = entityreference_get_selection_handler($field, $instance);
        $value = $handler->validateAutocompleteInput($element['#value'], $element, $form_state, $form);
      }
    }
    form_set_value($element, $value, $form_state);
  }
}
