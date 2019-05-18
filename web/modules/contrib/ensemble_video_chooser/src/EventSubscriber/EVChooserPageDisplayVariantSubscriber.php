<?php

namespace Drupal\ensemble_video_chooser\EventSubscriber;

use Drupal\Core\Render\PageDisplayVariantSelectionEvent;
use Drupal\Core\Render\RenderEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Selects the page display variant.
 */
class EVChooserPageDisplayVariantSubscriber implements EventSubscriberInterface {

  /**
   * Selects the page display variant.
   *
   * @param \Drupal\Core\Render\PageDisplayVariantSelectionEvent $event
   *   The event to process.
   */
  public function onSelectPageDisplayVariant(PageDisplayVariantSelectionEvent $event) {
    $route = $event->getRouteMatch()->getRouteName();
    if ($route == 'ensemble_video_chooser.launch' || $route == 'ensemble_video_chooser.return') {
      /*
       * Use the simple page variant for our routes.  Wish there was something
       * even 'simpler' but don't want to go through the motions of building
       * and maintaining our own variant.
       */
      $event->setPluginId('simple_page');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[RenderEvents::SELECT_PAGE_DISPLAY_VARIANT][] = array('onSelectPageDisplayVariant');
    return $events;
  }

}
