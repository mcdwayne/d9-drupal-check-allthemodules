<?php

namespace Drupal\og_sm_routing\Event;

use Drupal\node\NodeInterface;
use Drupal\og_sm\Event\SiteEvent;
use Symfony\Component\Routing\RouteCollection;

/**
 * Defines the site event.
 *
 * @see \Drupal\og_sm\Event\SiteEvents
 */
class SiteRoutingEvent extends SiteEvent {

  /**
   * The site route collection object.
   *
   * @var \Symfony\Component\Routing\RouteCollection
   */
  protected $routeCollection;

  /**
   * Constructs a site routing event object.
   *
   * @param \Drupal\node\NodeInterface $site
   *   The site node.
   * @param \Symfony\Component\Routing\RouteCollection $route_collection
   *  The site route collection object.
   */
  public function __construct(NodeInterface $site, RouteCollection $route_collection) {
    parent::__construct($site);
    $this->routeCollection = $route_collection;
  }

  /**
   * Gets the site route collection.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   The site route collection object.
   */
  public function getRouteCollection() {
    return $this->routeCollection;
  }

}
