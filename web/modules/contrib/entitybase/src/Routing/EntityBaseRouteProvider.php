<?php

/**
 * @file
 * Contains \Drupal\entity_base\Routing\EntityBaseRouteProvider.
 */

namespace Drupal\entity_base\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\EntityRouteProviderInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Defines dynamic routes.
 */
class EntityBaseRouteProvider implements EntityRouteProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $route_collection = new RouteCollection();

    // Admin list.
    $route = (new Route($entity_type->get('links')['collection']))
      ->addDefaults([
        '_entity_list' => $entity_type->id(),
        '_title' => $entity_type->get('entity_base')['names']['label_plural']->render(),
      ])
      ->setRequirement('_permission', 'administer ' . $entity_type->get('entity_base')['names']['base_plural'])
      ->setOption('_admin_route', TRUE);
    $route_collection->add('entity.' . $entity_type->id() . '.collection', $route);

    // Delete multiple.
    $route = (new Route($entity_type->get('links')['collection'] . '/delete'))
      ->addDefaults([
        '_form' => 'Drupal\entity_base\Form\DeleteMultiple',
      ])
      ->setRequirement('_permission', 'administer ' . $entity_type->get('entity_base')['names']['base_plural']);
    $route_collection->add('entity.' . $entity_type->id() . '.multiple_delete_confirm', $route);

    // Add entity.
    $route = (new Route($entity_type->get('links')['add-form']))
      ->addDefaults([
        '_entity_form' => $entity_type->id() . '.add',
        '_title' => 'Add ' . $entity_type->get('entity_base')['names']['label']->render(),
      ])
      ->setRequirement('_permission', 'administer ' . $entity_type->get('entity_base')['names']['base_plural'])
      ->setOption('_admin_route', TRUE);
    $route_collection->add('entity.' . $entity_type->id() . '.add_form', $route);

    // Entity view.
    $route = (new Route($entity_type->get('links')['canonical']))
      ->addDefaults([
        '_entity_view' => $entity_type->id() . '.full',
        '_title_callback' => $entity_type->get('entity_base')['callbacks']['entity.' . $entity_type->id() . '.canonical.title'],
      ])
      ->setRequirement($entity_type->id(), '\d+')
      ->setRequirement('_entity_access', $entity_type->id() . '.view');
    $route_collection->add('entity.' . $entity_type->id() . '.canonical', $route);

    // Edit entity.
    $route = (new Route($entity_type->get('links')['edit-form']))
      ->addDefaults([
        '_entity_form' => $entity_type->id() . '.edit',
        '_title' => 'Edit ' . $entity_type->get('entity_base')['names']['label']->render(),
      ])
      ->setRequirement('_entity_access', $entity_type->id() . '.update');
    $route_collection->add('entity.' . $entity_type->id() . '.edit_form', $route);

    // Delete entity.
    $route = (new Route($entity_type->get('links')['delete-form']))
      ->addDefaults([
        '_entity_form' => $entity_type->id() . '.delete',
        '_title' => 'Delete ' . $entity_type->get('entity_base')['names']['label']->render(),
      ])
      ->setRequirement('_entity_access', $entity_type->id() . '.delete');
    $route_collection->add('entity.' . $entity_type->id() . '.delete_form', $route);

    return $route_collection;
  }

}
