<?php

namespace Drupal\powertagging;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for PowerTagging entities.
 *
 * @see \Drupal\Core\Entity\Routing\AdminHtmlRouteProvider
 * @see \Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider
 */
class PowerTaggingConfigHtmlRouteProvider extends AdminHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $routes = parent::getRoutes($entity_type);

    $entity_type_id = $entity_type->id();

    if ($collection_route = $this->getCollectionRoute($entity_type)) {
      $routes->add("entity.{$entity_type_id}.collection", $collection_route);
    }

    if ($edit_config_route = $this->getEditConfigFormRoute($entity_type)) {
      $routes->add("entity.{$entity_type_id}.edit_config_form", $edit_config_route);
    }

    if ($clone_route = $this->getCloneFormRoute($entity_type)) {
      $routes->add("entity.{$entity_type_id}.clone_form", $clone_route);
    }

    return $routes;
  }

  /**
   * Gets the collection route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getCollectionRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('collection') && $entity_type->hasListBuilderClass() && ($admin_permission = $entity_type->getAdminPermission())) {
      $entity_type_id = $entity_type->id();
      $route = new Route($entity_type->getLinkTemplate('collection'));
      $route
        ->setDefaults([
          '_entity_list' => $entity_type_id,
          '_title' => (string) $entity_type->getLabel(),
        ])
        ->setRequirement('_permission', $admin_permission)
        ->setOption('_admin_route', TRUE);

      return $route;
    }

    return NULL;
  }

  /**
   * Gets the edit-config-form route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getEditConfigFormRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('edit-config-form') && ($admin_permission = $entity_type->getAdminPermission())) {
      $entity_type_id = $entity_type->id();
      $route = new Route($entity_type->getLinkTemplate('edit-config-form'));
      $route
        ->setDefaults([
          '_entity_form' => $entity_type_id . '.edit_config',
          '_title_callback' => '\Drupal\Core\Entity\Controller\EntityController::editTitle',
        ])
        ->setRequirement('_permission', $admin_permission)
        ->setOption('_admin_route', TRUE);

      return $route;
    }

    return NULL;
  }

  /**
   * Gets the clone-form route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getCloneFormRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('clone-form') && ($admin_permission = $entity_type->getAdminPermission())) {
      $entity_type_id = $entity_type->id();
      $route = new Route($entity_type->getLinkTemplate('clone-form'));
      $route
        ->setDefaults([
          '_entity_form' => $entity_type_id . '.clone',
          '_title' => t('Clone PowerTagging configuration'),
        ])
        ->setRequirement('_permission', $admin_permission)
        ->setOption('_admin_route', TRUE);

      return $route;
    }

    return NULL;
  }
}
