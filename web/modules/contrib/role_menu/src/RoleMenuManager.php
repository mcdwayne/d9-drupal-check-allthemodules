<?php

namespace Drupal\role_menu;

use Drupal\Core\Menu\MenuActiveTrailInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;

class RoleMenuManager implements RoleMenuManagerInterface {

  /**
   * The active menu trail service.
   *
   * @var \Drupal\Core\Menu\MenuActiveTrailInterface
   */
  protected $menuActiveTrail;

  /**
   * The menu link tree manager.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuLinkTree;

  public function __construct(MenuActiveTrailInterface $menu_active_trail, MenuLinkTreeInterface $menu_link_tree) {
    $this->menuActiveTrail = $menu_active_trail;
    $this->menuLinkTree = $menu_link_tree;
  }

  /**
   * @param $menu_id
   *
   * @return array $content
   */
  public function getMenuBlockContents($menu_id) {
    $link = $this->menuActiveTrail->getActiveLink($menu_id);

    if ($link && $content = \Drupal::service('system.manager')->getAdminBlock($link)) {
      $output = [
        '#theme' => 'admin_block_content',
        '#content' => $content,
      ];
    }
    elseif ($content = $this->getMenuBlock($menu_id)) {
      $output = [
        '#theme' => 'admin_block_content',
        '#content' => $content,
      ];
    }
    else {
      $output = [
        '#markup' => t('You do not have any menu items.'),
      ];
    }

    return $output;
  }

  public function getMenuBlock($menu_id) {
    $content = [];

    $parameters = new MenuTreeParameters();
    $parameters->setTopLevelOnly()->onlyEnabledLinks();

    $tree = $this->menuLinkTree->load($menu_id, $parameters);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators::generateIndexAndSort'],
    ];
    $tree = $this->menuLinkTree->transform($tree, $manipulators);
    $step = 1;
    foreach ($tree as $key => $element) {
      // Only render accessible links.
      if (!$element->access->isAllowed()) {
        // @todo Bubble cacheability metadata of both accessible and
        //   inaccessible links. Currently made impossible by the way admin
        //   blocks are rendered.
        continue;
      }

      $link = $element->link;
      $content[$key]['title'] = $link->getTitle();
      $content[$key]['options'] = $link->getOptions();
      $content[$key]['description'] = $link->getDescription();
      $content[$key]['url'] = $link->getUrlObject();

      $step++;
    }

    return $content;
  }

}