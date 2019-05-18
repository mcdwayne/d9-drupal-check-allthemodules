<?php

namespace Drupal\plus\Core\Render;

use Drupal\Core\Render\Renderer as CoreRenderer;
use Drupal\plus\Utility\Unicode;

/**
 * Modifies core's "renderer" service.
 */
class Renderer extends CoreRenderer {

  /**
   * Appends a theme hook suggestions to an element.
   *
   * @param array $element
   *   A render array element, passed by reference.
   * @param string $suggestion
   *   The suggestion to append.
   */
  protected function applyTypeSuggestions(array &$element, $suggestion) {
    // Iterate over theme hook properties.
    foreach (['theme_wrappers', 'theme'] as $property) {
      // Immediately continue if property is empty.
      if (empty($element["#$property"])) {
        continue;
      }

      // Check if property is an array of values.
      if (is_array($element["#$property"])) {
        foreach ($element["#$property"] as $key => $value) {
          $element["#$property"][$key] = $value . "__$suggestion";
        }
      }
      // Otherwise, just append the suggestion.
      else {
        $element["#$property"] .= "__$suggestion";
      }
    }
  }

  /**
   * Detects #type theme hook suggestions.
   *
   * This detects theme hook suggestions (e.g. __ ) found on the #type property
   * and proxies it to the corresponding #theme and/or #theme_wrappers
   * properties that actually interpret the theme hook suggestions. This is
   * particularly useful for when you don't know which property the element
   * implements.
   *
   * @code
   *   // Before.
   *   $build = [
   *     '#type' => 'button__my_suggestion',
   *   ];
   *
   *   // After.
   *   $build = [
   *     '#type' => 'button',
   *     '#theme_wrappers' => ['input__submit__my_suggestion'],
   *   ];
   * @endcode
   *
   * @param array $element
   *   A render array element, passed by reference.
   */
  protected function detectTypeSuggestions(array &$element) {
    // Proxy any suggestions added to "#type" to the necessary theme properties.
    if (isset($element['#type'])) {
      // Check if there's a theme hook suggestions on #type.
      $pos = Unicode::strpos($element['#type'], '__');

      // Append type suggestions to appropriate theme hook properties.
      if ($pos !== FALSE && ($suggestion = Unicode::substr($element['#type'], $pos + 2))) {
        // Reset the type to the real element type.
        $element['#type'] = Unicode::substr($element['#type'], 0, $pos);

        // Load element info (so #theme or #theme_wrappers are populated).
        if (empty($element['#defaults_loaded'])) {
          $element += $this->elementInfo->getInfo($element['#type']);
        }

        // Apply type suggestions.
        $this->applyTypeSuggestions($element, $suggestion);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doRender(&$elements, $is_root_call = FALSE) {
    // Detect #type theme hook suggestions.
    $this->detectTypeSuggestions($elements);

    // Let core handle the rest like it normally does.
    return parent::doRender($elements, $is_root_call);
  }

}
