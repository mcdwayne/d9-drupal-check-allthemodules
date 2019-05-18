<?php

namespace Drupal\og_sm_routing\EventSubscriber;

use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\node\NodeInterface;
use Drupal\og_sm\SiteManagerInterface;
use Drupal\og_sm_routing\Event\SiteRoutingEvent;
use Drupal\og_sm_routing\Event\SiteRoutingEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for site routes.
 */
class SiteRoutes implements EventSubscriberInterface {

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcher
   */
  protected $eventDispatcher;

  /**
   * The site manager.
   *
   * @var \Drupal\og_sm\SiteManagerInterface
   */
  protected $siteManager;

  /**
   * Constructs a PathProcessorAlias object.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher service.
   * @param \Drupal\og_sm\SiteManagerInterface $site_manager
   *   The site path manager.
   */
  public function __construct(EventDispatcherInterface $event_dispatcher, SiteManagerInterface $site_manager) {
    $this->eventDispatcher = $event_dispatcher;
    $this->siteManager = $site_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[RoutingEvents::DYNAMIC] = 'onDynamicRouteEvent';
    return $events;
  }

  /**
   * Alters existing routes for a specific collection.
   *
   * @param \Drupal\Core\Routing\RouteBuildEvent $event
   *   The route build event.
   * @return array
   */
  public function onDynamicRouteEvent(RouteBuildEvent $event) {
    $sites = $this->siteManager->getAllSites();
    foreach ($sites as $site) {
      $site_routes = $this->getRoutesForSite($site);
      $event->getRouteCollection()->addCollection($site_routes);
    }
  }

  /**
   * Provides all routes for a given site node.
   *
   * @param $site
   *   The site node.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   The route collection.
   */
  protected function getRoutesForSite(NodeInterface $site) {
    $collection = new RouteCollection();
    $event = new SiteRoutingEvent($site, $collection);
    // Collect all the routes for this site.
    $this->eventDispatcher->dispatch(SiteRoutingEvents::COLLECT, $event);

    // allow altering the routes.
    $this->eventDispatcher->dispatch(SiteRoutingEvents::ALTER, $event);

    // Prefix all the routes within the collection to avoid collision with other
    // site routes.
    foreach ($collection->all() as $route_name => $route) {
      $collection->remove($route_name);
      $route->addDefaults([
        'og_sm_routing:site' => $site,
      ]);
      $collection->add('og_sm_site:' . $site->id() . ':' . $route_name, $route);
    }

    return $collection;
  }

}
