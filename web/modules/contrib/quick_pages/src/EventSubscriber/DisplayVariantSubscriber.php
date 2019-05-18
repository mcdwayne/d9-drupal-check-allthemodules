<?php

/**
 * @file
 * Contains \Drupal\quick_pages\EventSubscriber\DisplayVariantSubscriber.
 */

namespace Drupal\quick_pages\EventSubscriber;

use Drupal\Core\Render\PageDisplayVariantSelectionEvent;
use Drupal\Core\Render\RenderEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Selects the display variant for quick pages.
 */
class DisplayVariantSubscriber implements EventSubscriberInterface {

  /**
   * Event callback.
   *
   * @param \Drupal\Core\Render\PageDisplayVariantSelectionEvent $event
   *   The event to process.
   */
  public function onSelectPageDisplayVariant(PageDisplayVariantSelectionEvent $event) {
    $display_variant = $event
      ->getRouteMatch()
      ->getRouteObject()
      ->getOption('display_variant');

    if ($display_variant['id']) {
      $event->setPluginId($display_variant['id']);
      $event->setPluginConfiguration($display_variant['configuration']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[RenderEvents::SELECT_PAGE_DISPLAY_VARIANT][] = ['onSelectPageDisplayVariant'];
    return $events;
  }

}
