<?php

namespace Drupal\menu_link_weight\MenuParentFormSelector;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Menu\MenuParentFormSelector;
use Drupal\Core\Menu\MenuTreeParameters;

/**
 * Implements Client-side hierarchical select (CSHS) as the menu parent form
 * selector.
 */
class CshsMenuParentFormSelector extends MenuParentFormSelector {

  /**
   * {@inheritdoc}
   */
  public function getParentSelectOptionsCshs($id = '', array $menus = NULL, CacheableMetadata &$cacheability = NULL) {
    if (!isset($menus)) {
      $menus = $this->getMenuOptions();
    }

    $options = [];
    $depth_limit = $this->getParentDepthLimit($id);
    foreach ($menus as $menu_name => $menu_title) {
      $options[$menu_name . ':'] = [
        'name' => '<' . $menu_title . '>',
        'parent_tid' => 0,
      ];

      $parameters = new MenuTreeParameters();
      $parameters->setMaxDepth($depth_limit);
      $tree = $this->menuLinkTree->load($menu_name, $parameters);
      $manipulators = [
        ['callable' => 'menu.default_tree_manipulators:checkNodeAccess'],
        ['callable' => 'menu.default_tree_manipulators:checkAccess'],
        ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
      ];
      $tree = $this->menuLinkTree->transform($tree, $manipulators);
      $this->parentSelectOptionsTreeWalkCshs($tree, $menu_name, $menu_name . ':', $options, $id, $depth_limit, $cacheability);
    }
    return $options;
  }


  /**
   * {@inheritdoc}
   */
  public function parentSelectElement($menu_parent, $id = '', array $menus = NULL) {
    $options_cacheability = new CacheableMetadata();
    $options = $this->getParentSelectOptionsCshs($id, $menus, $options_cacheability);
    // If no options were found, there is nothing to select.
    if ($options) {
      $element = [
        '#type' => 'cshs',
        '#options' => $options,
        '#attached' => [
          'library' => [
            'menu_link_weight/menu_parent_selector.cshs'
          ]
        ]
      ];
      if (!isset($options[$menu_parent])) {
        // The requested menu parent cannot be found in the menu anymore. Try
        // setting it to the top level in the current menu.
        list($menu_name, $parent) = explode(':', $menu_parent, 2);
        $menu_parent = $menu_name . ':';
      }
      if (isset($options[$menu_parent])) {
        // Only provide the default value if it is valid among the options.
        $element += ['#default_value' => $menu_parent];
      }
      $options_cacheability->applyTo($element);
      return $element;
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function parentSelectOptionsTreeWalkCshs(array $tree, $menu_name, $indent, array &$options, $exclude, $depth_limit, CacheableMetadata &$cacheability = NULL) {
    /** @var \Drupal\Core\Menu\MenuLinkTreeElement[] $tree */
    foreach ($tree as $element) {
      if ($element->depth > $depth_limit) {
        // Don't iterate through any links on this level.
        break;
      }

      // Collect the cacheability metadata of the access result, as well as the
      // link.
      if ($cacheability) {
        $cacheability = $cacheability
          ->merge(CacheableMetadata::createFromObject($element->access))
          ->merge(CacheableMetadata::createFromObject($element->link));
      }

      // Only show accessible links.
      if (!$element->access->isAllowed()) {
        continue;
      }

      $link = $element->link;
      if ($link->getPluginId() != $exclude) {
        $title = Unicode::truncate($link->getTitle(), 30, TRUE, FALSE);
        if (!$link->isEnabled()) {
          $title .= ' (' . $this->t('disabled') . ')';
        }
        $options[$menu_name . ':' . $link->getPluginId()] = [
          'name' => $title,
          'parent_tid' => $indent,
        ];
        if (!empty($element->subtree)) {
          $this->parentSelectOptionsTreeWalkCshs($element->subtree, $menu_name, $menu_name . ':' . $link->getPluginId(), $options, $exclude, $depth_limit, $cacheability);
        }
      }
    }
  }

}
