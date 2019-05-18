<?php

namespace Drupal\module_builder\Routing;

use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\Routing\Route;

/**
 * Route provider for component entity types.
 *
 * This expands data from the component_sections handler into routes for the
 * component entity form sections.
 */
class ComponentRouteProvider extends AdminHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);

    $entity_type_id = $entity_type->id();
    $admin_permission = $entity_type->getAdminPermission();

    $component_sections_handler = $this->entityTypeManager->getHandler($entity_type_id, 'component_sections');
    $section_route_data = $component_sections_handler->getFormTabRoutePaths();

    foreach ($section_route_data as $form_op => $title) {
      $route = new Route($entity_type->getLinkTemplate("{$form_op}-form"));
      $route
        ->setDefaults([
          '_entity_form' => "{$entity_type_id}.{$form_op}",
          '_title_callback' => '\Drupal\module_builder\Form\ComponentFormBase::title',
          'title' => $title,
          'op' => $form_op,
        ])
        ->setRequirement('_permission', $admin_permission)
        // TODO: needed???
        ->setOption('parameters', [
          $entity_type_id => ['type' => 'entity:' . $entity_type_id],
        ]);

      $collection->add("entity.{$entity_type_id}.{$form_op}_form", $route);
    }

    return $collection;
  }

}
