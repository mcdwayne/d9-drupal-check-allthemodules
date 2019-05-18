<?php

namespace Drupal\common;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Controller\ControllerResolverInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Template\Attribute;
// Use Drupal\Core\Menu\MenuTreeParameters;
// use Drupal\Core\Menu\MenuLinkTreeElement;.
use Drupal\Core\Menu\MenuLinkTreeInterface;

// Use Drupal\Core\Menu\MenuLinkTree;.
/**
 * Implements the loading, transforming and rendering of menu link trees.
 */
class ColorMenuLinkTree implements MenuLinkTreeInterface {
  /**
   * The menu link tree storage.
   *
   * @var \Drupal\Core\Menu\MenuTreeStorageInterface
   */
  protected $treeStorage;

  /**
   * The route provider to load routes by name.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * The active menu trail service.
   *
   * @var \Drupal\Core\Menu\MenuActiveTrailInterface
   */
  protected $menuActiveTrail;

  /**
   * The controller resolver.
   *
   * @var \Drupal\Core\Controller\ControllerResolverInterface
   */
  protected $controllerResolver;

  /**
   * Constructs a \Drupal\Core\Menu\MenuLinkTree object.
   *
   * @param \Drupal\Core\Menu\MenuTreeStorageInterface $tree_storage
   *   The menu link tree storage.
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager
   *   The menu link plugin manager.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider to load routes by name.
   * @param \Drupal\Core\Menu\MenuActiveTrailInterface $menu_active_trail
   *   The active menu trail service.
   * @param \Drupal\Core\Controller\ControllerResolverInterface $controller_resolver
   *   The controller resolver.
   */
  public function __construct(MenuTreeStorageInterface $tree_storage, MenuLinkManagerInterface $menu_link_manager, RouteProviderInterface $route_provider, MenuActiveTrailInterface $menu_active_trail, ControllerResolverInterface $controller_resolver) {
    $this->treeStorage = $tree_storage;
    $this->menuLinkManager = $menu_link_manager;
    $this->routeProvider = $route_provider;
    $this->menuActiveTrail = $menu_active_trail;
    $this->controllerResolver = $controller_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $tree, $level = 0) {
    parent::build($tree, $level);
    $items = [];

    foreach ($tree as $data) {
      $class = ['menu-item'];
      /** @var \Drupal\Core\Menu\MenuLinkInterface $link */
      $link = $data->link;
      // Generally we only deal with visible links, but just in case.
      if (!$link->isEnabled()) {
        continue;
      }
      // Set a class for the <li>-tag. Only set 'expanded' class if the link
      // also has visible children within the current tree.
      if ($data->hasChildren && !empty($data->subtree)) {
        $class[] = 'menu-item--expanded';
      }
      elseif ($data->hasChildren) {
        $class[] = 'menu-item--collapsed';
      }
      // Set a class if the link is in the active trail.
      if ($data->inActiveTrail) {
        $class[] = 'menu-item--active-trail';
      }

      // Allow menu-specific theme overrides.
      $element = [];
      $element['attributes'] = new Attribute();
      $element['attributes']['class'] = $class;
      $element['title'] = $link->getTitle();
      $element['url'] = $link->getUrlObject();
      $element['url']->setOption('set_active_class', TRUE);
      $element['below'] = $data->subtree ? $this->build($data->subtree, $level + 1) : [];
      if (isset($data->options)) {
        $element['url']->setOptions(NestedArray::mergeDeep($element['url']->getOptions(), $data->options));
      }
      $element['original_link'] = $link;
      // Index using the link's unique ID.
      $items[$link->getPluginId()] = $element;
    }
    if (!$items) {
      return [];
    }
    elseif ($level == 0) {
      $build = [];
      // Make sure drupal_render() does not re-order the links.
      $build['#sorted'] = TRUE;
      // Get the menu name from the last link.
      $menu_name = $link->getMenuName();
      // Add the theme wrapper for outer markup.
      // Allow menu-specific theme overrides.
      $build['#theme'] = 'menu__' . strtr($menu_name, '-', '_');
      $build['#items'] = $items;
      // Set cache tag.
      $build['#cache']['tags'][] = 'config:system.menu.' . $menu_name;
      return $build;
    }
    else {
      return $items;
    }
  }

}
