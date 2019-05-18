<?php

namespace Drupal\og_sm_routing;

use Drupal\node\NodeInterface;
use Drupal\og_sm_routing\Event\SiteRoutingEvent;
use Drupal\og_sm_routing\Event\SiteRoutingEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides a base implementation for RouteSubscriber.
 */
abstract class SiteRoutesSubscriberBase implements EventSubscriberInterface {

  /**
   * Collect routes for a specific site.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The route collection for adding routes.
   * @param \Drupal\node\NodeInterface $site
   *   The site node.
   */
  abstract protected function collectRoutes(RouteCollection $collection, NodeInterface $site);

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[SiteRoutingEvents::COLLECT] = 'onCollectRoutes';
    return $events;
  }

  /**
   * Delegates the site route collection to self::collectRoutes().
   *
   * @param \Drupal\og_sm_routing\Event\SiteRoutingEvent $event
   *   The route build event.
   */
  public function onCollectRoutes(SiteRoutingEvent $event) {
    $collection = $event->getRouteCollection();
    $site = $event->getSite();
    $this->collectRoutes($collection, $site);
  }

}
