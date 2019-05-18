<?php

namespace Drupal\block_in_form\Routing;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for Block in form routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The entity type manager
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

    // Create block in form routes for every entity.
    foreach ($this->manager->getDefinitions() as $entity_type_id => $entity_type) {
      $defaults = array();
      if ($route_name = $entity_type->get('field_ui_base_route')) {
        // Try to get the route from the current collection.
        if (!$entity_route = $collection->get($route_name)) {
          continue;
        }

        $path = sprintf('%s/block-in-form', $entity_route->getPath());

        $options = $entity_route->getOptions();

        // Special parameter used to easily recognize all Field UI routes.
        $options['_field_ui'] = TRUE;

        if (($bundle_entity_type = $entity_type->getBundleEntityType()) && $bundle_entity_type !== 'bundle') {
          $options['parameters'][$entity_type->getBundleEntityType()] = array(
            'type' => 'entity:' . $entity_type->getBundleEntityType(),
          );
        }

        $options['parameters']['block_in_form'] = array(
          'type' => 'block_in_form',
          'entity_type' => $entity_type->getBundleEntityType(),
        );

        $defaults_delete = [
          'entity_type_id' => $entity_type_id,
          '_form' => '\Drupal\block_in_form\Form\BlockInFormDeleteForm',
        ];
        $defaults_add = [
          'entity_type_id' => $entity_type_id,
          '_form' => '\Drupal\block_in_form\Form\BlockInFormAddForm',
          '_title' => 'Add Block',
        ];

        // If the entity type has no bundles and it doesn't use {bundle} in its
        // admin path, use the entity type.
        if (strpos($path, '{bundle}') === FALSE) {
          $defaults_add['bundle'] = !$entity_type->hasKey('bundle') ? $entity_type_id : '';
          $defaults_delete['bundle'] = $defaults_add['bundle'];
        }

        // Routes to delete block in form.
        $route = new Route(
          "$path/form-display/{block_in_form_name}/delete",
          ['context' => 'form'] + $defaults_delete,
          array('_permission' => 'administer ' . $entity_type_id . ' form display'),
          $options
        );
        $collection->add("field_ui.block_in_form_delete_$entity_type_id.form_display", $route);

        $route = new Route(
          "$path/form-display/{form_mode_name}/{block_in_form_name}/delete",
          ['context' => 'form'] + $defaults_delete,
          array('_permission' => 'administer ' . $entity_type_id . ' form display'),
          $options
        );
        $collection->add("field_ui.block_in_form_delete_$entity_type_id.form_display.form_mode", $route);

        $route = new Route(
          "$path/display/{block_in_form_name}/delete",
          ['context' => 'view'] + $defaults_delete,
          array('_permission' => 'administer ' . $entity_type_id . ' display'),
          $options
        );
        $collection->add("field_ui.block_in_form_delete_$entity_type_id.display", $route);

        $route = new Route(
          "$path/display/{view_mode_name}/{block_in_form_name}/delete",
          ['context' => 'view'] + $defaults_delete,
          array('_permission' => 'administer ' . $entity_type_id . ' display'),
          $options
        );
        $collection->add("field_ui.block_in_form_delete_$entity_type_id.display.view_mode", $route);

        // Routes to add block in form.
        $route = new Route(
          "$path/form-display/add-block",
          ['context' => 'form'] + $defaults_add,
          array('_permission' => 'administer ' . $entity_type_id . ' form display'),
          $options
        );
        $collection->add("field_ui.block_in_form_add_$entity_type_id.form_display", $route);

        $route = new Route(
          "$path/form-display/{form_mode_name}/add-block",
          ['context' => 'form'] + $defaults_add,
          array('_permission' => 'administer ' . $entity_type_id . ' form display'),
          $options
        );
        $collection->add("field_ui.block_in_form_add_$entity_type_id.form_display.form_mode", $route);

        $route = new Route(
          "$path/display/add-block",
          ['context' => 'view'] + $defaults_add,
          array('_permission' => 'administer ' . $entity_type_id . ' display'),
          $options
        );
        $collection->add("field_ui.block_in_form_add_$entity_type_id.display", $route);

        $route = new Route(
          "$path/display/{view_mode_name}/add-block",
          ['context' => 'view'] + $defaults_add,
          array('_permission' => 'administer ' . $entity_type_id . ' display'),
          $options
        );
        $collection->add("field_ui.block_in_form_add_$entity_type_id.display.view_mode", $route);

      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    //$events = parent::getSubscribedEvents();
    // Come after field_ui, config_translation.
    $events[RoutingEvents::ALTER] = array('onAlterRoutes', -210);
    return $events;
  }

}
