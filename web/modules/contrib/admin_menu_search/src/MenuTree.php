<?php

namespace Drupal\admin_menu_search;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\toolbar\Menu\ToolbarMenuLinkTree;

/**
 * Class MenuTree.
 */
class MenuTree {

  use StringTranslationTrait;

  /**
   * Toolbar Menu Tree.
   *
   * @var \Drupal\toolbar\Menu\ToolbarMenuLinkTree
   */
  protected $toolbarMenuTree;

  /**
   * Cache bin.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Constructs a new MenuTree object.
   */
  public function __construct(ToolbarMenuLinkTree $toolbar_menu_tree, CacheBackendInterface $cache) {
    $this->toolbarMenuTree = $toolbar_menu_tree;
    $this->cache = $cache;
  }

  /**
   * Method to get current language id.
   *
   * @return string
   *   Language id
   */
  protected function getCurrentLanguageId() {
    return \Drupal::languageManager()->getCurrentLanguage()->getId();
  }

  /**
   * Method to get cache id.
   *
   * @return string
   *   Cache id
   */
  protected function getCacheId() {
    return 'admin_menu_search:' . $this->getCurrentLanguageId();
  }

  /**
   * Method to get cache object.
   *
   * @return CacheBackend
   *   Cached data
   */
  protected function getCache() {
    $cid = $this->getCacheId();
    return $this->cache->get($cid);
  }

  /**
   * Method to set cache object.
   *
   * @param array $menu_tree_index
   *   Toolbar menu hierarchy.
   */
  protected function setCache(array $menu_tree_index) {
    $cid = $this->getCacheId();
    $this->cache->set($cid, $menu_tree_index);
  }

  /**
   * Method to get toolbar menu tree.
   *
   * @return array
   *   Menu hierarchy tree
   */
  protected function getToolbarMenuTree() {
    $parameters = new MenuTreeParameters();
    $parameters->setRoot('system.admin')
      ->excludeRoot()
      ->setMaxDepth(4)
      ->onlyEnabledLinks();
    return $this->toolbarMenuTree->load(NULL, $parameters);
  }

  /**
   * Build menu tree index.
   *
   * @param array $tree
   *   Toolbar menu link tree.
   * @param array $menu_tree_index
   *   Toolbar menu link tree index.
   * @param string $parent
   *   Parent menu.
   */
  public function buildMenuTreeIndex(array $tree, array &$menu_tree_index, $parent = '') {
    foreach ($tree as $branch) {
      $link = $branch->link;
      $menu_definition = $link->getPluginDefinition();
      // Escape direct cache flush and cron links, which needs dynamic token.
      if (stripos($menu_definition['route_name'], 'flush') !== FALSE
          || $menu_definition['route_name'] == 'admin_toolbar.run.cron') {
        continue;
      }
      if (is_object($menu_definition['title'])) {
        $menu_title = $menu_definition['title']->render();
      }
      elseif (is_string($menu_definition['title'])) {
        $menu_title = $this->t($menu_definition['title']);
      }
      if (!empty($parent)) {
        $menu_title = $parent . ' » ' . $menu_title;
      }
      $menu_tree_index[] = [
        'name' => $menu_definition['route_name'],
        'title' => $menu_title,
        'parameters' => $menu_definition['route_parameters'],
      ];
      if ($branch->subtree) {
        $this->buildMenuTreeIndex($branch->subtree, $menu_tree_index, $menu_title);
      }
    }
  }

  /**
   * Method to get admin toolbar menu index.
   *
   * @return array
   *   Admin menu index.
   */
  public function getAdminToolbarMenuIndex() {
    if ($cache = $this->getCache()) {
      $menu_tree_index = $cache->data;
    }
    else {
      $tree = $this->getToolbarMenuTree();
      $menu_tree_index = [];
      $this->buildMenuTreeIndex($tree, $menu_tree_index);
      $this->setCache($menu_tree_index);
    }

    return $menu_tree_index;
  }

}
