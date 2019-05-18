<?php

namespace Drupal\bcubed_adreplace\Plugin\bcubed\Event;

use Drupal\bcubed\EventBase;

/**
 * Provides basic page load event.
 *
 * @Event(
 *   id = "ad_replaced",
 *   label = @Translation("Replacement Ad Loaded"),
 *   description = @Translation("Fires when an AdReplace action completes"),
 *   bcubed_dependencies = {
 *     {
 *      "plugin_type" = "action",
 *      "plugin_id" = "ad_replace",
 *      "same_set" = false,
 *      "dependency_type" = "generated_by",
 *     }
 *   }
 * )
 */
class AdReplaced extends EventBase {

  /**
   * {@inheritdoc}
   */
  public function getEventName() {
    return 'replacementAdLoaded';
  }

}
