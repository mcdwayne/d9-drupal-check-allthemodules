<?php

namespace Drupal\pluginreference\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Textfield;

/**
 * Provides a plugin autocomplete form element.
 *
 * The #default_value accepted by this element is a plugin definition array.
 *
 * @FormElement("plugin_autocomplete")
 */
class PluginAutocomplete extends Textfield {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    $class = get_class($this);

    // Apply default form element properties.
    $info['#target_type'] = NULL;

    $info['#element_validate'] = array(
      array(
        $class,
        'validatePluginAutocomplete'
      )
    );
    array_unshift($info['#process'], array(
      $class,
      'processPluginAutocomplete'
    ));

    return $info;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    // Process the #default_value property.
    if ($input === FALSE && isset($element['#default_value'])) {
      if ($element['#default_value'] && isset($element['#default_value']['label'])) {
        return $element['#default_value']['label'] . ' (' . $element['#default_value']['id'] . ')';
      }
    }

    return NULL;
  }

  /**
   * Adds plugin autocomplete functionality to a form element.
   *
   * @param array $element
   *   The form element to process. Properties used:
   *   - #target_type: The ID of the target entity type.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The form element.
   *
   * @throws \InvalidArgumentException
   *   Exception thrown when the #target_type is missing.
   */
  public static function processPluginAutocomplete(array &$element, FormStateInterface $form_state, array &$complete_form) {
    // Nothing to do if there is no target entity type.
    if (empty($element['#target_type'])) {
      throw new \InvalidArgumentException('Missing required #target_type parameter.');
    }

    $element['#autocomplete_route_name'] = 'pluginreference.plugin_autocomplete';
    $element['#autocomplete_route_parameters'] = [
      'target_type' => $element['#target_type'],
    ];

    return $element;
  }

  /**
   * Form element validation handler for plugin_autocomplete elements.
   */
  public static function validatePluginAutocomplete(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $value = NULL;

    if (!empty($element['#value']) && $match = static::extractPluginIdFromAutocompleteInput($element['#value'])) {
      $handler = \Drupal::service('plugin.manager.' . $element['#target_type']);
      if ($handler->hasDefinition($match)) {
        $value = $match;
      }
    }

    $form_state->setValueForElement($element, $value);
  }

  /**
   * Helper function to retrieve the plugin ID from the element value.
   */
  public static function extractPluginIdFromAutocompleteInput($input) {
    $match = NULL;

    // Take "label (plugin id)', match the ID from parenthesis when it's a
    // number.
    if (preg_match("/.+\s\((\d+)\)/", $input, $matches)) {
      $match = $matches[1];
    }
    // Match the ID when it's a string .
    elseif (preg_match("/.+\s\(([\w.]+)\)/", $input, $matches)) {
      $match = $matches[1];
    }

    return $match;
  }

}
