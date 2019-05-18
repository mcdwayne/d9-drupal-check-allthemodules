<?php

namespace Drupal\og_sm_routing_test\EventSubscriber;

use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\og_sm\Event\SiteEvent;
use Drupal\og_sm\Event\SiteEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listens to the site events.
 */
class SiteSubscriber implements EventSubscriberInterface {

  /**
   * The route builder.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routeBuilder;

  /**
   * Constructs a SiteSubscriber object.
   *
   * @param \Drupal\Core\Routing\RouteBuilderInterface $route_builder
   *   The route builder.
   */
  public function __construct(RouteBuilderInterface $route_builder) {
    $this->routeBuilder = $route_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[SiteEvents::INSERT][] = 'onSiteInsert';
    $events[SiteEvents::DELETE][] = 'onSiteDelete';
    $events[SiteEvents::UPDATE][] = 'onSiteUpdate';
    return $events;
  }

  /**
   * Event listener triggered when a site is inserted.
   *
   * @param \Drupal\og_sm\Event\SiteEvent $event
   *   The site event.
   */
  public function onSiteInsert(SiteEvent $event) {
    $this->routeBuilder->rebuild();
  }

  /**
   * Event listener triggered when a site is removed.
   *
   * @param \Drupal\og_sm\Event\SiteEvent $event
   *   The site event.
   */
  public function onSiteDelete(SiteEvent $event) {
    $this->routeBuilder->rebuild();
  }

  /**
   * Event listener triggered when a site is updated.
   *
   * @param \Drupal\og_sm\Event\SiteEvent $event
   *   The site event.
   */
  public function onSiteUpdate(SiteEvent $event) {
    $site = $event->getSite();
    if (isset($site->original) && $site->original->isPublished() !== $site->isPublished()) {
      $this->routeBuilder->rebuild();
    }
  }

}
