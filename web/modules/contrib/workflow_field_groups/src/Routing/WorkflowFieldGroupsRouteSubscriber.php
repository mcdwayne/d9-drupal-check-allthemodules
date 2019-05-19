<?php

namespace Drupal\workflow_field_groups\Routing;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for Field UI routes.
 */
class WorkflowFieldGroupsRouteSubscriber extends RouteSubscriberBase {

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

        if (!$entity_route = $collection->get($route_name)) {
          continue;
        }

        if ($entity_type_id == 'workflow_transition') {
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

        foreach (['view', 'edit'] as $operation) {
          // Route for the default form mode.
          $route = new Route(
            "$path/workflow-field-groups/default/$operation",
            [
              '_form' => '\Drupal\workflow_field_groups\Form\WorkflowFieldGroupsForm',
              '_title' => 'Workflow field groups',
              'form_mode_name' => 'default',
              'form_operation' => $operation,
            ] + $defaults,
            ['_workflow_field_groups_access' => 'administer workflow field groups'],
            $options
          );

          $route_name = "workflow_field_groups.$entity_type_id.workflow.default.$operation";

          $collection->add($route_name, $route);

          // Route for different form modes and operations.
          $route = new Route(
            "$path/workflow-field-groups/{form_mode_name}/{form_operation}",
            [
              '_form' => '\Drupal\workflow_field_groups\Form\WorkflowFieldGroupsForm',
              '_title' => 'Workflow field groups',
            ] + $defaults,
            ['_workflow_field_groups_access' => "administer workflow field groups"],
            $options
          );

          $route_name = "workflow_field_groups.$entity_type_id.workflow.form_mode.form_operation";

          $collection->add($route_name, $route);
        }
      }
    }
  }

}
