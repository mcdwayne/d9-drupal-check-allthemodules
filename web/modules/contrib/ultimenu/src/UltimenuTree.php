<?php

namespace Drupal\ultimenu;

use Drupal\Component\Utility\Html;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuActiveTrailInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\system\Entity\Menu;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides Ultimenu utility methods.
 */
class UltimenuTree implements UltimenuTreeInterface {

  use StringTranslationTrait;

  /**
   * The menu link tree manager.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuTree;

  /**
   * The active menu trail service.
   *
   * @var \Drupal\Core\Menu\MenuActiveTrailInterface
   */
  protected $menuActiveTrail;

  /**
   * Constructs a UltimenuTree object.
   */
  public function __construct(MenuLinkTreeInterface $menu_tree, MenuActiveTrailInterface $menu_active_trail) {
    $this->menuTree = $menu_tree;
    $this->menuActiveTrail = $menu_active_trail;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('menu.link_tree'),
      $container->get('menu.active_trail')
    );
  }

  /**
   * Returns the menu tree.
   */
  public function getMenuTree() {
    return $this->menuTree;
  }

  /**
   * Returns the menu active trail.
   */
  public function getMenuActiveTrail() {
    return $this->menuActiveTrail;
  }

  /**
   * {@inheritdoc}
   */
  public function getMenus() {
    $custom_menus = [];
    if ($menus = Menu::loadMultiple()) {
      foreach ($menus as $menu_name => $menu) {
        $custom_menus[$menu_name] = Html::escape($menu->label());
      }
    }

    $excluded_menus = [
      'admin' => $this->t('Administration'),
      'devel' => $this->t('Development'),
      'tools' => $this->t('Tools'),
    ];

    $options = array_diff_key($custom_menus, $excluded_menus);
    asort($options);
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function loadMenuTree($menu_name) {
    $parameters = new MenuTreeParameters();
    $parameters->setTopLevelOnly()->onlyEnabledLinks();

    $tree = $this->menuTree->load($menu_name, $parameters);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
      ['callable' => 'menu.default_tree_manipulators:flatten'],
    ];
    $tree = $this->menuTree->transform($tree, $manipulators);

    return $tree;
  }

  /**
   * {@inheritdoc}
   */
  public function loadSubMenuTree($menu_name, $link_id, $title = '') {
    $build = [];
    $level = 1;
    $depth = 4;

    $parameters = $this->menuTree->getCurrentRouteMenuTreeParameters($menu_name);
    $parameters->setRoot($link_id)->excludeRoot()->onlyEnabledLinks();
    $parameters->setMaxDepth(min($level + $depth - 1, $this->menuTree->maxDepth()));

    $tree = $this->menuTree->load($menu_name, $parameters);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $tree = $this->menuTree->transform($tree, $manipulators);

    if ($tree) {
      $build['content'] = $this->menuTree->build($tree);
      $css_name = Html::cleanCssIdentifier(mb_strtolower($menu_name . '-' . $title));
      $build['#attributes']['class'] = ['ultimenusub', 'ultimenusub--' . $css_name];
      $build['#theme_wrappers'][] = 'container';
    }

    return $build;
  }

}
