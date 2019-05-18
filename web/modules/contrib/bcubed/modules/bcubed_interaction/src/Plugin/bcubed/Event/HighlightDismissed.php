<?php

namespace Drupal\bcubed_interaction\Plugin\bcubed\Event;

use Drupal\bcubed\EventBase;

/**
 * Provides event on highlight dismiss.
 *
 * @Event(
 *   id = "highlight_dismissed",
 *   label = @Translation("Highlight Dismissed"),
 *   description = @Translation("Fires every time an Element Highlight is dismissed"),
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
class HighlightDismissed extends EventBase {

  /**
   * {@inheritdoc}
   */
  public function getEventName() {
    return 'ElementHighlightDismissed';
  }

}
