<?php

namespace Drupal\wsdata;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for Web Service Call entities.
 *
 * @see Drupal\Core\Entity\Routing\AdminHtmlRouteProvider
 * @see Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider
 */
class WSCallHtmlRouteProvider extends AdminHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);

    $entity_type_id = $entity_type->id();

    if ($collection_route = $this->getCollectionRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.collection", $collection_route);
    }

    return $collection;
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
    if ($entity_type->hasLinkTemplate('collection') && $entity_type->hasListBuilderClass()) {
      $entity_type_id = $entity_type->id();
      $route = new Route($entity_type->getLinkTemplate('collection'));
      $route
        ->setDefaults([
          '_entity_list' => $entity_type_id,
          // Make sure this is not a TranslatableMarkup object as the
          // TitleResolver translates this string again.
          '_title' => (string) $entity_type->getLabel(),
        ])
        ->setRequirement('_permission', $entity_type->getAdminPermission())
        ->setOption('_admin_route', TRUE);

      return $route;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getCanonicalRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('canonical')) {
      $entity_type_id = $entity_type->id();
      $route = new Route($entity_type->getLinkTemplate('canonical'));
      // Use the edit form handler, if available, otherwise default.
      $operation = 'default';
      if ($entity_type->getFormClass('test')) {
        $operation = 'test';
      }
      $route
      ->setDefaults([
        '_entity_form' => "{$entity_type_id}.{$operation}",
        '_title_callback' => '\Drupal\Core\Entity\Controller\EntityController::title'
      ])
      ->setRequirement('_entity_access', "{$entity_type_id}.view")
      ->setOption('parameters', [
        $entity_type_id => ['type' => 'entity:' . $entity_type_id],
      ]);

      // Entity types with serial IDs can specify this in their route
      // requirements, improving the matching process.
      if ($this->getEntityTypeIdKeyType($entity_type) === 'integer') {
        $route->setRequirement($entity_type_id, '\d+');
      }
      return $route;
    }
  }

}
