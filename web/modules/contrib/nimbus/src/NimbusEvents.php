<?php

namespace Drupal\nimbus;

/**
 * Defines events for the config system.
 */
final class NimbusEvents {

  /**
   * Name of the event when selecting a page display variant to use.
   *
   * This event allows you to select a different page display variant to use
   * when rendering a page. The event listener method receives a
   * \Drupal\Core\Render\PageDisplayVariantSelectionEvent instance.
   *
   * @Event
   *
   * @see \Drupal\Core\Render\PageDisplayVariantSelectionEvent
   * @see \Drupal\Core\Render\MainContent\HtmlRenderer
   * @see \Drupal\block\EventSubscriber\BlockPageDisplayVariantSubscriber
   */
  const ADD_PATH = 'nimbus.add_path';

}
