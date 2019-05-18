<?php

namespace Drupal\efs\Routing;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for Field group routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

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

    // Create fieldgroup routes for every entity.
    foreach ($this->manager->getDefinitions() as $entity_type_id => $entity_type) {
      $defaults = [];
      if ($route_name = $entity_type->get('field_ui_base_route')) {
        // Try to get the route from the current collection.
        if (!$entity_route = $collection->get($route_name)) {
          continue;
        }
        $path = $entity_route->getPath();

        $options = $entity_route->getOptions();

        // Special parameter used to easily recognize all Field UI routes.
        $options['_field_ui'] = TRUE;

        if (($bundle_entity_type = $entity_type->getBundleEntityType()) && $bundle_entity_type !== 'bundle') {
          $options['parameters'][$entity_type->getBundleEntityType()] = [
            'type' => 'entity:' . $entity_type->getBundleEntityType(),
          ];
        }

        $options['parameters']['efs'] = [
          'type' => 'efs',
          'entity_type' => $entity_type->getBundleEntityType(),
        ];

        $defaults_add = [
          'entity_type_id' => $entity_type_id,
          '_form' => '\Drupal\efs\Form\ExtraFieldAddForm',
          '_title' => 'Add extra field',
        ];

        // If the entity type has no bundles and it doesn't use {bundle} in its
        // admin path, use the entity type.
        if (strpos($path, '{bundle}') === FALSE) {
          $defaults_add['bundle'] = !$entity_type->hasKey('bundle') ? $entity_type_id : '';
          $defaults_delete['bundle'] = $defaults_add['bundle'];
        }

        // Routes to add field groups.
        $route = new Route(
          "$path/form-display/add-extra-field",
          ['context' => 'form'] + $defaults_add,
          ['_permission' => 'administer ' . $entity_type_id . ' form display'],
          $options
        );
        $collection->add("field_ui.efs_add_$entity_type_id.form_display", $route);

        $route = new Route(
          "$path/form-display/{form_mode_name}/add-extra-field",
          ['context' => 'form'] + $defaults_add,
          ['_permission' => 'administer ' . $entity_type_id . ' form display'],
          $options
        );
        $collection->add("field_ui.efs_add_$entity_type_id.form_display.form_mode", $route);

        $route = new Route(
          "$path/display/add-extra-field",
          ['context' => 'view'] + $defaults_add,
          ['_permission' => 'administer ' . $entity_type_id . ' display'],
          $options
        );
        $collection->add("field_ui.efs_add_$entity_type_id.display", $route);

        $route = new Route(
          "$path/display/{view_mode_name}/add-extra-field",
          ['context' => 'view'] + $defaults_add,
          ['_permission' => 'administer ' . $entity_type_id . ' display'],
          $options
        );
        $collection->add("field_ui.efs_add_$entity_type_id.display.view_mode", $route);

      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // $events = parent::getSubscribedEvents();
    // Come after field_ui, config_translation.
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -210];
    return $events;
  }

}
