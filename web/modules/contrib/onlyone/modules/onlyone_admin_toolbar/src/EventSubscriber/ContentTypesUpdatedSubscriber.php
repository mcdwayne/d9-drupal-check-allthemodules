<?php

namespace Drupal\onlyone_admin_toolbar\EventSubscriber;

use Drupal\Core\Routing\RouteBuilderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\onlyone\OnlyOneEvents;

/**
 * Class ContentTypesUpdatedSubscriber.
 */
class ContentTypesUpdatedSubscriber implements EventSubscriberInterface {

  /**
   * The route builder.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routeBuilder;

  /**
   * Constructor.
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
    $events = [];
    $events[OnlyOneEvents::CONTENT_TYPES_UPDATED][] = ['rebuildMenu', 0];

    return $events;
  }

  /**
   * Subscriber Callback for the event OnlyOneEvents::CONTENT_TYPES_UPDATED.
   */
  public function rebuildMenu() {
    $this->routeBuilder->rebuild();
  }

}
