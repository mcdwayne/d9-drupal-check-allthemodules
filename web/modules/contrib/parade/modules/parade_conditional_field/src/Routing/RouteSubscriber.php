<?php

namespace Drupal\parade_conditional_field\Routing;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for Field UI routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $manager;

  /**
   * Route matcher service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * Constructs a RouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $manager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $routeMatch
   *   Route match service.
   */
  public function __construct(
    EntityTypeManagerInterface $manager,
    CurrentRouteMatch $routeMatch
  ) {
    $this->manager = $manager;
    $this->routeMatch = $routeMatch;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach ($this->manager->getDefinitions() as $entityTypeId => $entityType) {
      if ($entityTypeId === 'paragraph' && $routeName = $entityType->get('field_ui_base_route')) {
        // Try to get the route from the current collection.
        if (!$entityRoute = $collection->get($routeName)) {
          continue;
        }
        $path = $entityRoute->getPath();
        $options = $entityRoute->getOptions();
        if ($bundleEntityType = $entityType->getBundleEntityType()) {
          $options['parameters'][$bundleEntityType] = [
            'type' => 'entity:' . $bundleEntityType,
          ];
        }
        // @todo: pass paragraphs_type as bundle.
        $bundle = $this->routeMatch->getParameter('paragraphs_type');

        $defaults = [
          'entity_type_id' => $entityTypeId,
          'bundle' => $bundle,
        ];

        $route = new Route(
          "$path/parade-conditional-fields",
          [
            '_controller' => '\Drupal\parade_conditional_field\Controller\ParadeConditionalFieldController::listing',
            '_title' => 'Parade field conditions',
          ] + $defaults,
          ['_permission' => 'administer paragraphs types'],
          $options
        );
        $collection->add("entity.{$entityTypeId}.parade_conditional_field", $route);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -100];
    return $events;
  }

}
