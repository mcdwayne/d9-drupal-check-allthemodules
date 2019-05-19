<?php

namespace Drupal\workflow_participants\Routing;

use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Route;

/**
 * Route subscriber to alter access for content moderation routes.
 */
class RouteSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[RoutingEvents::ALTER] = ['alterRoutes', 50];
    return $events;
  }

  /**
   * Alter content moderation route permissions callbacks.
   *
   * @param \Drupal\Core\Routing\RouteBuildEvent $event
   *   The route build event.
   */
  public function alterRoutes(RouteBuildEvent $event) {
    // @todo This is hard-coded for nodes.
    // @see https://www.drupal.org/node/2922348
    $collection = $event->getRouteCollection();

    // Revision history tab.
    if ($route = $collection->get('entity.node.version_history')) {
      $this->applyRevisionsCheck($route);
    }

    // Node revision.
    if ($route = $collection->get('entity.node.revision')) {
      $this->applyRevisionsCheck($route);
    }

    // Diff module's version comparison.
    if ($route = $collection->get('diff.revisions_diff')) {
      $this->applyRevisionsCheck($route);
    }
  }

  /**
   * Adds the revision access check.
   *
   * This also removes the `_access_node_revision` check, and that is checked
   * by the `_workflow_participants_revision` check.
   *
   * @todo Investigate decorating the node access check instead.
   */
  protected function applyRevisionsCheck(Route $route) {
    $route->setRequirement('_workflow_participants_revision', 'view');

    // Remove the `_access_node_revision` check.
    $requirements = $route->getRequirements();
    unset($requirements['_access_node_revision']);
    $route->setRequirements($requirements);
  }

}
