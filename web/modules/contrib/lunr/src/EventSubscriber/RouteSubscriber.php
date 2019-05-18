<?php

namespace Drupal\lunr\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for all Lunr searches.
 */
class RouteSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * RouteSubscriber constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Provides routes for Lunr searches.
   *
   * @param \Drupal\Core\Routing\RouteBuildEvent $event
   *   The route build event.
   */
  public function onDynamicRoutes(RouteBuildEvent $event) {
    $collection = $event->getRouteCollection();
    $defaults = [
      '_controller' => 'Drupal\lunr\Controller\LunrSearchController::page',
      '_title_callback' => 'Drupal\lunr\Controller\LunrSearchController::title',
    ];
    $requirements = [
      '_permission' => 'access content',
      '_custom_access' => 'Drupal\lunr\Controller\LunrSearchController::access',
    ];
    /** @var \Drupal\lunr\LunrSearchInterface $lunr_search */
    foreach ($this->entityTypeManager->getStorage('lunr_search')->loadMultiple() as $lunr_search) {
      $collection->add('lunr_search.' . $lunr_search->id(), new Route($lunr_search->getPath(), [
        'lunr_search' => $lunr_search->id(),
      ] + $defaults, $requirements));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[RoutingEvents::DYNAMIC] = 'onDynamicRoutes';
    return $events;
  }

}
