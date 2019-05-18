<?php

namespace Drupal\entity_gallery\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\EntityRouteProviderInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides routes for entity galleries.
 */
class EntityGalleryRouteProvider implements EntityRouteProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function getRoutes( EntityTypeInterface $entity_type) {
    $route_collection = new RouteCollection();
    $route = (new Route('/gallery/{entity_gallery}'))
      ->addDefaults([
        '_controller' => '\Drupal\entity_gallery\Controller\EntityGalleryViewController::view',
        '_title_callback' => '\Drupal\entity_gallery\Controller\EntityGalleryViewController::title',
      ])
      ->setRequirement('entity_gallery', '\d+')
      ->setRequirement('_entity_access', 'entity_gallery.view');
    $route_collection->add('entity.entity_gallery.canonical', $route);

    $route = (new Route('/gallery/{entity_gallery}/delete'))
      ->addDefaults([
        '_entity_form' => 'entity_gallery.delete',
        '_title' => 'Delete',
      ])
      ->setRequirement('entity_gallery', '\d+')
      ->setRequirement('_entity_access', 'entity_gallery.delete')
      ->setOption('_entity_gallery_operation_route', TRUE);
    $route_collection->add('entity.entity_gallery.delete_form', $route);

    $route = (new Route('/gallery/{entity_gallery}/edit'))
      ->setDefault('_entity_form', 'entity_gallery.edit')
      ->setRequirement('_entity_access', 'entity_gallery.update')
      ->setRequirement('entity_gallery', '\d+')
      ->setOption('_entity_gallery_operation_route', TRUE);
    $route_collection->add('entity.entity_gallery.edit_form', $route);

    return $route_collection;
  }

}
