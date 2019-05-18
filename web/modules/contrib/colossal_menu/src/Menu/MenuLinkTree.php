<?php

/**
 * @file
 * Contains \Drupal\colossal_menu\Menu\MenuLinkTree.
 */

namespace Drupal\colossal_menu\Menu;

use Drupal\Core\Access\AccessibleInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerResolverInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Menu\MenuActiveTrailInterface;
use Drupal\Core\Menu\MenuLinkTreeElement;
use Drupal\Core\Menu\MenuLinkTree as CoreMenuLinkTree;
use Drupal\Core\Menu\MenuTreeStorageInterface;
use Drupal\Core\Routing\RouteProviderInterface;

/**
 * Implements the loading, transforming and rendering of menu link trees.
 */
class MenuLinkTree extends CoreMenuLinkTree {

  /**
   * Entity Manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface.
   */
  protected $entityManager;

  /**
   * Constructs a \Drupal\Core\Menu\MenuLinkTree object.
   *
   * @param \Drupal\Core\Menu\MenuTreeStorageInterface $tree_storage
   *   The menu link tree storage.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider to load routes by name.
   * @param \Drupal\Core\Menu\MenuActiveTrailInterface $menu_active_trail
   *   The active menu trail service.
   * @param \Drupal\Core\Controller\ControllerResolverInterface $controller_resolver
   *   The controller resolver.
   */
  public function __construct(MenuTreeStorageInterface $tree_storage,
                              RouteProviderInterface $route_provider,
                              MenuActiveTrailInterface $menu_active_trail,
                              ControllerResolverInterface $controller_resolver,
                              EntityManagerInterface $entity_manager) {
    $this->treeStorage = $tree_storage;
    $this->routeProvider = $route_provider;
    $this->menuActiveTrail = $menu_active_trail;
    $this->controllerResolver = $controller_resolver;
    $this->entityManager = $entity_manager;
  }


  /**
   * {@inheritdoc}
   */
  protected function createInstances(array $data_tree) {
    $tree = [];
    foreach ($data_tree as $key => $element) {
      $subtree = $this->createInstances($element['subtree']);
      // Build a MenuLinkTreeElement out of the menu tree link definition:
      // transform the tree link definition into a link definition and store
      // tree metadata.
      $tree[$key] = new MenuLinkTreeElement(
        $element['link'],
        (bool) $element['has_children'],
        (int) $element['depth'],
        (bool) $element['in_active_trail'],
        $subtree
      );

      if ($tree[$key]->link instanceof AccessibleInterface) {
        $tree[$key]->access = $tree[$key]->link->access('view', NULL, TRUE);
      }
    }
    return $tree;
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $tree) {
    $build = parent::build($tree);

    // Use a custom theme.
    if (isset($build['#theme'])) {
      $build['#theme'] = 'colossal_menu__' . strtr($build['#menu_name'], '-', '_');
    }

    if (!empty($build['#items'])) {
      $this->addItemContent($build['#items']);
    }

    return $build;
  }

  /**
   * Add the Link Content and add a no link variable.
   *
   * @param array $tree
   *   Tree of links.
   */
  protected function addItemContent(array &$tree) {
    foreach ($tree as &$item) {
      $link = $item['original_link'];

      $item['show_title'] = $link->showTitle();
      $item['identifier'] = Html::cleanCssIdentifier($link->getMachineName());

      $item['has_link'] = TRUE;
      if (!$link->isExternal() && $link->getRouteName() == '<none>') {
        $item['has_link'] = FALSE;
      }

      $item['content'] = $this->entityManager->getViewBuilder($link->getEntityTypeId())->view($link, 'default');
      if (!empty($item['below'])) {
        $this->addItemContent($item['below']);
      }
    }
  }

}
