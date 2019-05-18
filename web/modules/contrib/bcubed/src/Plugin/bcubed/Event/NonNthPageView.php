<?php

namespace Drupal\bcubed\Plugin\bcubed\Event;

use Drupal\bcubed\EventBase;

/**
 * Provides basic page load event.
 *
 * @Event(
 *   id = "non_nth_page_view",
 *   label = @Translation("Non Nth Page View Event"),
 *   description = @Translation("Loads on every page where the nth page view condition is not true"),
 *   bcubed_dependencies = {
 *     {
 *      "plugin_type" = "condition",
 *      "plugin_id" = "nth_page_view",
 *      "same_set" = false,
 *      "dependency_type" = "generated_by",
 *     }
 *   }
 * )
 */
class NonNthPageView extends EventBase {

  /**
   * {@inheritdoc}
   */
  public function getEventName() {
    return 'bcubedNonNthPageView';
  }

  /**
   * {@inheritdoc}
   */
  public function getLibrary() {
    return 'bcubed/nthpageview';
  }

}
