<?php

namespace Drupal\monster_menus;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\EntityRouteProviderInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides routes for nodes.
 */
class MMTreeHtmlRouteProvider implements EntityRouteProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $route_collection = new RouteCollection();

    $options = [
      'parameters' => [
        'mm_tree' => [
          'tempstore' => TRUE,
          'type' => 'entity:mm_tree',
        ]
      ]
    ];

    $route = (new Route('/mm/{mm_tree}'))
      ->addDefaults([
        '_controller' => '\Drupal\monster_menus\Controller\MMTreeViewController::view',
        '_title_callback' => '\Drupal\monster_menus\Controller\MMTreeViewController::title',
      ])
      ->addOptions($options)
      ->setRequirement('mm_tree', '-?\d+')
      ->setRequirement('_custom_access', '\Drupal\monster_menus\Controller\DefaultController::menuAccessUserCan');
    $route_collection->add('entity.mm_tree.canonical', $route);

    $route = (new Route('/mm/{mm_tree}/settings/sub'))
      ->addDefaults([
        'op' => 'sub',
        '_controller' => '\Drupal\monster_menus\Controller\DefaultController::handlePageSettings',
        '_title_callback' => '\Drupal\monster_menus\Controller\DefaultController::menuGetTitleSettingsSub',
      ])
      ->addOptions($options)
      ->setRequirement('mm_tree', '-?\d+')
      ->setRequirement('_custom_access', '\Drupal\monster_menus\Controller\DefaultController::menuAccessSub');
    $route_collection->add('entity.mm_tree.add_form', $route);

    $route = (new Route('/mm/{mm_tree}/settings/delete'))
      ->addDefaults([
        'op' => 'delete',
        '_controller' => '\Drupal\monster_menus\Controller\DefaultController::handlePageSettings',
        '_title_callback' => '\Drupal\monster_menus\Controller\DefaultController::menuGetTitleSettingsDelete',
      ])
      ->addOptions($options)
      ->setRequirement('mm_tree', '-?\d+')
      ->setRequirement('_custom_access', '\Drupal\monster_menus\Controller\DefaultController::menuAccessDelete');
    $route_collection->add('entity.mm_tree.delete_form', $route);

    $route = (new Route('/mm/{mm_tree}/settings/edit'))
      ->addDefaults([
        'op' => 'edit',
        '_controller' => '\Drupal\monster_menus\Controller\DefaultController::handlePageSettings',
        '_title' => 'Edit',
      ])
      ->addOptions($options)
      ->setRequirement('mm_tree', '-?\d+')
      ->setRequirement('_custom_access', '\Drupal\monster_menus\Controller\DefaultController::menuAccessEdit');
    $route_collection->add('entity.mm_tree.edit_form', $route);

    $route = (new Route('/mm/{mm_tree}/settings/revisions'))
      ->addDefaults([
        '_controller' => '\Drupal\monster_menus\Controller\DefaultController::showRevisions',
        '_title' => 'Revisions',
      ])
      ->addOptions($options)
      ->setRequirement('mm_tree', '-?\d+')
      ->setRequirement('_permission', 'see create/modify times');
    $route_collection->add('entity.mm_tree.version_history', $route);

    return $route_collection;
  }

}
