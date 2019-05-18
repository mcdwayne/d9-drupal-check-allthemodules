<?php

namespace Drupal\field_tools\Routing;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for Field Tools routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a RouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($route_name = $entity_type->get('field_ui_base_route')) {
        // Try to get the route from the current collection.
        if (!$entity_route = $collection->get($route_name)) {
          continue;
        }
        $path = $entity_route->getPath();

        $options = $entity_route->getOptions();
        if ($bundle_entity_type = $entity_type->getBundleEntityType()) {
          $options['parameters'][$bundle_entity_type] = array(
            'type' => 'entity:' . $bundle_entity_type,
          );
        }
        // Special parameter used to easily recognize all Field UI routes.
        $options['_field_ui'] = TRUE;

        $defaults = array(
          'entity_type_id' => $entity_type_id,
        );
        // If the entity type has no bundles and it doesn't use {bundle} in its
        // admin path, use the entity type.
        if (strpos($path, '{bundle}') === FALSE) {
          $defaults['bundle'] = !$entity_type->hasKey('bundle') ? $entity_type_id : '';
        }

        // Route for cloning a single field.
        $route = new Route(
          "$path/fields/{field_config}/clone",
          array(
            '_entity_form' => 'field_config.clone',
            '_title' => 'Clone field',
          ) + $defaults,
          // TODO!
          array('_entity_access' => 'field_config.update'),
          $options
        );
        $collection->add("entity.field_config.{$entity_type_id}_field_tools_clone_form", $route);

        // Route for bulk cloning fields.
        $route = new Route(
          "$path/fields/tools/clone-fields",
          array(
            '_form' => '\Drupal\field_tools\Form\FieldBulkCloneForm',
            '_title' => 'Clone fields',
          ) + $defaults,
          array('_permission' => 'administer ' . $entity_type_id . ' fields'),
          $options
        );
        $collection->add("field_tools.field_bulk_clone_$entity_type_id", $route);

        // Route for bulk cloning displays.
        $route = new Route(
          "$path/fields/tools/clone-displays",
          array(
            '_form' => '\Drupal\field_tools\Form\EntityDisplayBulkCloneForm',
            '_title' => 'Clone displays',
          ) + $defaults,
          array('_permission' => 'administer ' . $entity_type_id . ' fields'),
          $options
        );
        $collection->add("field_tools.displays_clone_$entity_type_id", $route);

        // Route for bulk copying field display settings.
        $route = new Route(
          "$path/fields/tools/copy-display-settings",
          array(
            '_form' => '\Drupal\field_tools\Form\EntityDisplaySettingsBulkCopyForm',
            '_title' => 'Copy display settings',
          ) + $defaults,
          array('_permission' => 'administer ' . $entity_type_id . ' fields'),
          $options
        );
        $collection->add("field_tools.displays_settings_copy_$entity_type_id", $route);

        // Route for exporting field configuration to code.
        $route = new Route(
          "$path/fields/tools/export-to-code",
          array(
            '_form' => '\Drupal\field_tools\Form\ConfigFieldsExportToCodeForm',
            '_title' => 'Export to base fields code',
          ) + $defaults,
          array('_permission' => 'administer ' . $entity_type_id . ' fields'),
          $options
        );
        $collection->add("field_tools.export_to_code_$entity_type_id", $route);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();
    $events[RoutingEvents::ALTER] = array('onAlterRoutes', -100);
    return $events;
  }

}
