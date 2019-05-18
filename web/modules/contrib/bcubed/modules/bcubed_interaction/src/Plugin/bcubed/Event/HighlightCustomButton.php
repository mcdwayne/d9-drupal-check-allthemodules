<?php

namespace Drupal\bcubed_interaction\Plugin\bcubed\Event;

use Drupal\bcubed\EventBase;

/**
 * Provides event on highlight custom button fire.
 *
 * @Event(
 *   id = "highlight_custom_button",
 *   label = @Translation("Highlight Custom Button"),
 *   description = @Translation("Fires every time an Element Highlight custom button is pressed"),
 *   bcubed_dependencies = {
 *     {
 *      "plugin_type" = "action",
 *      "plugin_id" = "highlight_element",
 *      "same_set" = false,
 *      "dependency_type" = "generated_by",
 *     }
 *   }
 * )
 */
class HighlightCustomButton extends EventBase {

  /**
   * {@inheritdoc}
   */
  public function getEventName() {
    return 'ElementHighlightCustomButton';
  }

}
