<?php

namespace Drupal\bcubed\Plugin\bcubed\Event;

use Drupal\bcubed\EventBase;

/**
 * Provides basic page load event.
 *
 * @Event(
 *   id = "page_load",
 *   label = @Translation("Page Load"),
 *   description = @Translation("Fires on every page load")
 * )
 */
class PageLoad extends EventBase {

  /**
   * {@inheritdoc}
   */
  public function getLibrary() {
    return 'bcubed/pageload';
  }

  /**
   * {@inheritdoc}
   */
  public function getEventName() {
    return 'bcubedPageLoad';
  }

}
