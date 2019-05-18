<?php

/**
 * @file
 * Contains \Drupal\field_presets\Routing\FieldPresetsRouteSubscriber.
 */

namespace Drupal\field_presets\Routing;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for Field UI routes.
 */
class FieldPresetsRouteSubscriber extends RouteSubscriberBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $manager;

  /**
   * Constructs a RouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $manager
   *   The entity type manager.
   */
  public function __construct(EntityManagerInterface $manager) {
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach ($this->manager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($route_name = $entity_type->get('field_ui_base_route')) {

        // Taken from field_ui - thank you :)
        if (!$entity_route = $collection->get($route_name)) {
          continue;
        }

        $path = $entity_route->getPath();

        $defaults = [
          'entity_type_id' => $entity_type_id,
          'ref_route' => 'entity.' . $entity_type_id . '.field_ui_fields',
        ];

        $options = $entity_route->getOptions();
        if ($bundle_entity_type = $entity_type->getBundleEntityType()) {
          $options['parameters'][$bundle_entity_type] = [
            'type' => 'entity:' . $bundle_entity_type,
          ];

          $defaults['bundle_entity_type'] = $bundle_entity_type;
        }

        // Special parameter used to easily recognize all Field UI routes.
        $options['_field_ui'] = TRUE;

        $route = new Route(
          "$path/fields/add-field-using-preset",
          [
            '_form' => '\Drupal\field_presets\Form\FieldPresetsForm',
            '_title' => 'Add field using preset',
          ] + $defaults,
          ['_permission' => 'administer ' . $entity_type_id . ' fields'],
          $options
        );

        $route_name = 'field_presets.' . $entity_type_id . '.add_field_using_preset';

        $collection->add($route_name, $route);
      }
    }
  }

}
