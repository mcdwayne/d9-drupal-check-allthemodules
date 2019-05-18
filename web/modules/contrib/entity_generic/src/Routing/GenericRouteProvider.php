<?php

namespace Drupal\entity_generic\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\entity\Routing\DefaultHtmlRouteProvider;
use Symfony\Component\Routing\Route;

/**
 * Defines dynamic routes.
 */
class GenericRouteProvider extends DefaultHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);

    $entity_type_id = $entity_type->id();
    $entity_bundle_id = $entity_type->getBundleEntityType();
    $entity_bundle = $entity_bundle_id ? \Drupal::service('entity_type.manager')->getDefinition($entity_bundle_id) : FALSE;

    // $collection = new RouteCollection();
    $entity_bundle_links = $entity_bundle ? $entity_bundle->get('links') : [];
    $entity_type_links = $entity_type ? $entity_type->get('links') : [];
    $entity_type_callbacks = $entity_type && $entity_type->get('entity_generic') && isset($entity_type->get('entity_generic')['callbacks']) ? $entity_type->get('entity_generic')['callbacks'] : [];

    // Handle entities with bundles.
    if ($entity_bundle) {

      // Add entity with modal form.
      if (
        isset($entity_type_links['collection'])
        && isset($entity_type_callbacks['entity.' . $entity_type_id . '.add_entity_modal_title'])
        && isset($entity_type_callbacks['entity.' . $entity_type_id . '.add_entity_modal'])
      ) {
        $route = (new Route($entity_type_links['collection'] . '/add-modal/' . '{' . $entity_bundle_id . '}'))
          ->addDefaults([
            '_controller' => $entity_type_callbacks['entity.' . $entity_type_id . '.add_entity_modal'],
            '_title_callback' => $entity_type_callbacks['entity.' . $entity_type_id . '.add_entity_modal_title'],
          ])
          ->setOption('_admin_route', TRUE)
          ->setOption('operation', 'add')
          ->setOption('parameters',  array(
            $entity_bundle_id => array(
              'type' => 'entity:' . $entity_bundle_id,
              'with_config_overrides' => TRUE,
            ),
          ))
          ->setRequirement('_entity_create_access', $entity_type_id . ':{' . $entity_bundle_id . '}');
        $collection->add('entity.' . $entity_type_id . '.add_modal_form', $route);
      }

    }
    // Handle entities without bundles.
    else {

      // Add entity with modal form.
      if (isset($entity_type_links['add-modal-form'])) {
        $route = (new Route($entity_type_links['add-modal-form']))
          ->addDefaults([
            '_controller' => $entity_type_callbacks['entity.' . $entity_type_id . '.add_entity_modal'],
            '_title' => 'Add ' . $entity_type->getSingularLabel(),
          ])
          ->setRequirement('_permission', 'create ' . $entity_type_id)
          ->setOption('_admin_route', TRUE);
        $collection->add('entity.' . $entity_type_id . '.add_modal_form', $route);
      }

    }

    // Edit entity with modal form.
    if (
      isset($entity_type_links['edit-modal-form'])
      && isset($entity_type_callbacks['entity.' . $entity_type_id . '.edit_entity_modal'])
    ) {
      $route = (new Route($entity_type_links['edit-modal-form']))
        ->addDefaults([
          '_controller' => $entity_type_callbacks['entity.' . $entity_type_id . '.edit_entity_modal'],
          '_title' => 'Edit ' . $entity_type->getSingularLabel(),
        ])
        ->setRequirement($entity_type_id, '\d+')
        ->setRequirement('_entity_access', $entity_type_id . '.update');
      $collection->add('entity.' . $entity_type_id . '.edit_modal_form', $route);
    }

    // Delete entity with modal form.
    if (
      isset($entity_type_links['delete-modal-form'])
      && isset($entity_type_callbacks['entity.' . $entity_type_id . '.delete_entity_modal'])
    ) {
      $route = (new Route($entity_type_links['delete-modal-form']))
        ->addDefaults([
          '_controller' => $entity_type_callbacks['entity.' . $entity_type_id . '.delete_entity_modal'],
          '_title' => 'Delete ' . $entity_type->getSingularLabel(),
        ])
        ->setRequirement($entity_type_id, '\d+')
        ->setRequirement('_entity_access', $entity_type_id . '.delete');
      $collection->add('entity.' . $entity_type_id . '.delete_modal_form', $route);
    }

    // Toggle status.
    if (
      isset($entity_type_links['toggle-status-form'])
      && $entity_type_callbacks['entity.' . $entity_type_id . '.toggle_status']
    ) {
      $route = (new Route($entity_type_links['status-toggle']))
        ->addDefaults([
          '_controller' => $entity_type_callbacks['entity.' . $entity_type_id . '.status_toggle'],
          '_title' => 'Toggle status for ' . $entity_type->getSingularLabel(),
        ])
        ->setRequirement($entity_type_id, '\d+')
        ->setRequirement('_entity_access', $entity_type_id . '.update');
      $collection->add('entity.' . $entity_type_id . '.status_toggle', $route);
    }

    // Toggle status with AJAX.
    if (
      isset($entity_type_links['toggle-status-modal-form'])
      && $entity_type_callbacks['entity.' . $entity_type_id . '.toggle_status_modal']
    ) {
      $route = (new Route($entity_type_links['toggle-status-modal-form']))
        ->addDefaults([
          '_controller' => $entity_type_callbacks['entity.' . $entity_type_id . '.toggle_status_modal'],
          '_title' => 'Toggle status for ' . $entity_type->getSingularLabel(),
        ])
        ->setRequirement($entity_type_id, '\d+')
        ->setRequirement('_entity_access', $entity_type_id . '.update');
      $collection->add('entity.' . $entity_type_id . '.status_toggle_modal', $route);
    }

    if ($entity_type->isRevisionable()) {
      // Entity revision history.
      if (
        isset($entity_type_links['revision-history'])
        && isset($entity_type_callbacks['entity.' . $entity_type_id . '.revision_history'])
        && is_callable($entity_type_callbacks['entity.' . $entity_type_id . '.revision_history'])
      ) {
        $route = (new Route($entity_type_links['revision-history']))
          ->addDefaults([
            '_title' => 'Revisions',
            '_controller' => $entity_type_callbacks['entity.' . $entity_type_id . '.revision_history'],
          ])
          ->setRequirement($entity_type_id, '\d+')
          ->setRequirement('_entity_access_revision', 'view')
          ->setOption('_admin_route', TRUE);
        $collection->add('entity.' . $entity_type_id . '.revision_history', $route);
      }

      // View entity revision.
      if (
        isset($entity_type_links['revision'])
        && isset($entity_type_callbacks['entity.' . $entity_type_id . '.revision'])
        && is_callable($entity_type_callbacks['entity.' . $entity_type_id . '.revision'])
        && isset($entity_type_callbacks['entity.' . $entity_type_id . '.revision.title'])
        && is_callable($entity_type_callbacks['entity.' . $entity_type_id . '.revision.title'])
      ) {
        $route = (new Route($entity_type_links['revision']))
          ->addDefaults([
            '_controller' => $entity_type_callbacks['entity.' . $entity_type_id . '.revision'],
            '_title_callback' => $entity_type_callbacks['entity.' . $entity_type_id . '.revision.title'],
          ])
          ->setRequirement($entity_type_id, '\d+')
          ->setRequirement('_entity_access_revision', 'view');
        $collection->add('entity.' . $entity_type_id . '.revision', $route);
      }
    }

    if ($merge_multiple_route = $this->getMergeMultipleFormRoute($entity_type)) {
      $collection->add('entity.' . $entity_type->id() . '.merge_multiple_form', $merge_multiple_route);
    }

    return $collection;
  }

  /**
   * Returns the merge multiple form route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getMergeMultipleFormRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('merge-multiple-form') && $entity_type->hasHandlerClass('form', 'merge-multiple-confirm')) {
      $route = new Route($entity_type->getLinkTemplate('merge-multiple-form'));
      $route->setDefault('_form', $entity_type->getFormClass('merge-multiple-confirm'));
      $route->setDefault('entity_type_id', $entity_type->id());
      $route->setRequirement('_entity_generic_merge_multiple_access', $entity_type->id());
      return $route;
    }
  }

}
