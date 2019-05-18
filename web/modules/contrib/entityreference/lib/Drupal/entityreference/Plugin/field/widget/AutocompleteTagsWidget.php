<?php

/**
 * @file
 * Definition of Drupal\entityreference\Plugin\field\widget\AutocompleteTagsWidget.
 */

namespace Drupal\entityreference\Plugin\field\widget;

use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\field\Plugin\Type\Widget\WidgetBase;

use Drupal\entityreference\Plugin\field\widget\AutocompleteWidgetBase;

/**
 * Plugin implementation of the 'entityreference autocomplete-tags' widget.
 *
 * @todo: Check if the following statement is still correct
 * The autocomplete path doesn't have a default here, because it's not the
 * the two widgets, and the Field API doesn't update default settings when
 * the widget changes.
 *
 * @Plugin(
 *   id = "entityreference_autocomplete_tags",
 *   module = "entityreference",
 *   label = @Translation("Autocomplete (Tags style)"),
 *   description = @Translation("An autocomplete text field."),
 *   field_types = {
 *     "entityreference"
 *   },
 *   settings = {
 *     "match_operator" = "CONTAINS",
 *     "size" = 60,
 *     "path" = ""
 *   },
 *   multiple_values = FIELD_BEHAVIOR_CUSTOM
 * )
 */
class AutocompleteTagsWidget extends AutocompleteWidgetBase {

  /**
   * Implements Drupal\field\Plugin\Type\Widget\WidgetInterface::formElement().
   */
  public function formElement(array $items, $delta, array $element, $langcode, array &$form, array &$form_state) {
    return $this->prepareElement($items, $delta, $element, $langcode, $form, $form_state, 'entityreference/autocomplete/tags');
  }

  /**
   * Implements Drupal\entityreference\Plugin\field\widget\DefaultAutocompleteWidget::elementValidate()
   */
  public function elementValidate($element, &$form_state) {
    $value = array();
    // If a value was entered into the autocomplete.
    if (!empty($element['#value'])) {
      $entities = drupal_explode_tags($element['#value']);
      $value = array();
      foreach ($entities as $entity) {
        // Take "label (entity id)', match the id from parenthesis.
        if (preg_match("/.+\((\d+)\)/", $entity, $matches)) {
          $value[] = array(
            'target_id' => $matches[1],
          );
        }
        else {
          // Try to get a match from the input string when the user didn't use the
          // autocomplete but filled in a value manually.
        $instance = field_info_instance($element['#entity_type'], $element['#field_name'], $element['#bundle']);
        $handler = entityreference_get_selection_handler($field, $instance);
          $value[] = array(
            'target_id' => $handler->validateAutocompleteInput($entity, $element, $form_state, $form),
          );
        }
      }
    }
    form_set_value($element, $value, $form_state);
  }
}
