<?php

namespace Drupal\og_sm_routing_test\EventSubscriber;

use Drupal\node\NodeInterface;
use Drupal\og_sm_routing\Event\SiteRoutingEvent;
use Drupal\og_sm_routing\Event\SiteRoutingEvents;
use Drupal\og_sm_routing\SiteRoutesSubscriberBase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for site routes.
 */
class SiteRoutesSubscriber extends SiteRoutesSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();
    $events[SiteRoutingEvents::ALTER] = 'alterRoutes';
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  protected function collectRoutes(RouteCollection $collection, NodeInterface $site) {
    // For this implementation of the collect routes event we are going to
    // create a page based on whether a node is published or not.
    // This example was chosen for simplicity, creating pages based on the
    // site's published state should probably be done with custom access checks.
    // Sites routes are more useful in combination with the og_sm_config module.
    if (!$site->isPublished()) {
      return;
    }

    $route = new Route(
      '/group/node/' . $site->id() . '/published',
      [
        '_controller' => '\Drupal\og_sm_routing_test\Controller\PublishedController::published',
        '_title' => 'This is a published site.',
      ],
      [
        '_access' => 'TRUE',
      ]
    );
    $collection->add('og_sm_routing_test.published', $route);
  }

  /**
   * Event listener triggered during site route collection.
   *
   * @param \Drupal\og_sm_routing\Event\SiteRoutingEvent $event
   *   The route build event.
   */
  public function alterRoutes(SiteRoutingEvent $event) {
    $route = $event->getRouteCollection()->get('og_sm_routing_test.published');

    if ($route) {
      $route->setPath('/group/node/' . $event->getSite()->id() . '/is-published');
    }
  }

}
