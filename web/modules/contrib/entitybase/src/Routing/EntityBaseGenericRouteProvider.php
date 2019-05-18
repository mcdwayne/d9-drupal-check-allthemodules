<?php

/**
 * @file
 * Contains \Drupal\entity_base\Routing\EntityBaseGenericRoutes.
 */

namespace Drupal\entity_base\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\EntityRouteProviderInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Defines dynamic routes.
 */
class EntityBaseGenericRouteProvider implements EntityRouteProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $entity_bundle = \Drupal::service('entity_type.manager')->getDefinition($entity_type->get('bundle_entity_type'));
    $route_collection = new RouteCollection();

    // Admin entity bundles.
    $route = (new Route($entity_bundle->get('links')['collection']))
      ->addDefaults([
        '_entity_list' => $entity_bundle->id(),
        '_title' => $entity_bundle->get('entity_base_type')['names']['label_plural']->render(),
      ])
      ->setRequirement('_permission', 'administer ' . $entity_bundle->get('entity_base_type')['names']['base_plural'])
      ->setOption('_admin_route', TRUE);
    $route_collection->add('entity.' . $entity_bundle->id() . '.collection', $route);

    // Add entity bundle.
    $route = (new Route($entity_bundle->get('links')['add-form']))
      ->addDefaults([
        '_entity_form' => $entity_bundle->id() . '.add',
        '_title' => 'Add ' . $entity_bundle->get('entity_base_type')['names']['label']->render(),
      ])
      ->setRequirement('_permission', 'administer ' . $entity_bundle->get('entity_base_type')['names']['base_plural'])
      ->setOption('_admin_route', TRUE);
    $route_collection->add('entity.' . $entity_bundle->id() . '.add_form', $route);

    // Edit entity bundle.
    $route = (new Route($entity_bundle->get('links')['edit-form']))
      ->addDefaults([
        '_entity_form' => $entity_bundle->id() . '.edit',
        '_title' => 'Edit ' . $entity_bundle->get('entity_base_type')['names']['label']->render(),
      ])
      ->setRequirement('_permission', 'administer ' . $entity_bundle->get('entity_base_type')['names']['base_plural'])
      ->setOption('_admin_route', TRUE);
    $route_collection->add('entity.' . $entity_bundle->id() . '.edit_form', $route);

    // Delete entity bundle.
    $route = (new Route($entity_bundle->get('links')['delete-form']))
      ->addDefaults([
        '_entity_form' => $entity_bundle->id() . '.delete',
        '_title' => 'Delete ' . $entity_bundle->get('entity_base_type')['names']['label']->render(),
      ])
      ->setRequirement('_permission', 'administer ' . $entity_bundle->get('entity_base_type')['names']['base_plural'])
      ->setOption('_admin_route', TRUE);
    $route_collection->add('entity.' . $entity_bundle->id() . '.delete_form', $route);

    // Admin entity list.
    $route = (new Route($entity_type->get('links')['collection']))
      ->addDefaults([
        '_entity_list' => $entity_type->id(),
        '_title' => $entity_type->get('entity_base')['names']['label_plural']->render(),
      ])
      ->setRequirement('_permission', 'administer ' . $entity_type->get('entity_base')['names']['base_plural'])
      ->setOption('_admin_route', TRUE);
    $route_collection->add('entity.' . $entity_type->id() . '.collection', $route);

    // Add entity page.
    $route = (new Route($entity_type->get('links')['collection'] . '/add'))
      ->addDefaults([
        '_controller' => $entity_type->get('entity_base')['callbacks']['entity.' . $entity_type->id() . '.add_page'],
        '_title' => 'Add ' . $entity_type->get('entity_base')['names']['label']->render(),
      ])
      ->setRequirement('_permission', 'administer ' . $entity_type->get('entity_base')['names']['base_plural'])
      ->setOption('_admin_route', TRUE);
    $route_collection->add($entity_type->id() . '.add_page', $route);

    // Add entity.
    $route = (new Route($entity_type->get('links')['collection'] . '/add/' . '{' . $entity_bundle->id() . '}'))
      ->addDefaults([
        '_controller' => $entity_type->get('entity_base')['callbacks']['entity.' . $entity_type->id() . '.add_entity'],
        '_title_callback' => $entity_type->get('entity_base')['callbacks']['entity.' . $entity_type->id() . '.add_page_title'],
      ])
      ->setOption('_admin_route', TRUE)
      ->setOption('operation', 'add')
      ->setOption('parameters',  array(
        $entity_bundle->id() => array(
          'type' => 'entity:' . $entity_bundle->id(),
          'with_config_overrides' => TRUE,
        ),
      ))
      ->setRequirement('_entity_base_access_check', 'administer ' . $entity_type->get('entity_base')['names']['base_plural']);
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

    // Entity revision history.
//    $route = (new Route($entity_type->get('links')['revision-history']))
//      ->addDefaults([
//        '_title' => 'Revisions',
//        '_controller' => $entity_type->get('entity_base')['callbacks']['entity.' . $entity_type->id() . '.revision_history'],
//      ])
//      ->setRequirement($entity_type->id(), '\d+')
//      ->setRequirement('_entity_base_revision_access_check', 'view')
//      ->setOption('_admin_route', TRUE);
//    $route_collection->add('entity.' . $entity_type->id() . '.revision_history', $route);

    // View entity revision.
//    $route = (new Route($entity_type->get('links')['revision']))
//      ->addDefaults([
//        '_controller' => $entity_type->get('entity_base')['callbacks']['entity.' . $entity_type->id() . '.revision'],
//        '_title_callback' => $entity_type->get('entity_base')['callbacks']['entity.' . $entity_type->id() . '.revision.title'],
//      ])
//      ->setRequirement($entity_type->id(), '\d+')
//      ->setRequirement('_entity_base_revision_access_check', 'view');
//    $route_collection->add('entity.' . $entity_type->id() . '.revision', $route);

    return $route_collection;
  }

}
