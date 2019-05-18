<?php

namespace Drupal\repec\Routing;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * RouteSubscriber.
 */
class RouteSubscriber extends RouteSubscriberBase {

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
   *   Entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();
    $events[RoutingEvents::ALTER] = ['onAlterRoutes'];
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if (!$route_name = $entity_type->get('field_ui_base_route')) {
        continue;
      }

      // Try to get the routes from the current collection.
      if (!$entity_route = $collection->get($route_name)) {
        continue;
      }

      $path = $entity_route->getPath();

      $options = $entity_route->getOptions();
      if ($bundle_entity_type = $entity_type->getBundleEntityType()) {
        $options['parameters'][$bundle_entity_type] = [
          'type' => 'entity:' . $bundle_entity_type,
        ];
      }

      // Special parameter used to easily recognize all Field UI routes.
      $options['_field_ui'] = TRUE;

      $defaults = [
        'entity_type_id' => $entity_type_id,
      ];

      $route = new Route(
        "$path/repec",
        [
          '_form' => '\Drupal\repec\Form\EntityTypeSettingsForm',
          '_title' => 'RePEc',
        ] + $defaults,
        ['_permission' => 'administer repec'],
        $options
      );
      $collection->add("entity.$entity_type_id.repec", $route);
    }
  }

}
