<?php

namespace Drupal\sitemap\Plugin\Sitemap;

use Drupal\sitemap\SitemapBase;
use Drupal\Core\Template\Attribute;
use Drupal\system\Entity\Menu;
use Drupal\Core\Menu\MenuTreeParameters;

/**
 * Provides a link to the front page for the sitemap.
 *
 * @Sitemap(
 *   id = "sitemap_menus",
 *   title = @Translation("Menu name"),
 *   description = @Translation("Menu description"),
 *   settings = {
 *     "title" = "",
 *   },
 *   deriver = "Drupal\sitemap\Plugin\Derivative\SitemapMenus",
 *   enabled = FALSE,
 *   menu = "",
 * )
 */
class SitemapMenus extends SitemapBase {

  /**
   * {@inheritdoc}
   */
  public function view() {
    $title = $this->settings['title'];

    $menu_id = $this->pluginDefinition['menu'];
    $menu = Menu::load($menu_id);
    // Retrieve the expanded tree.
    $tree = \Drupal::service('menu.link_tree')->load($menu_id, new MenuTreeParameters());
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $tree = \Drupal::service('menu.link_tree')->transform($tree, $manipulators);

    // Add an alter hook so that other modules can manipulate the
    // menu tree prior to rendering.
    $alter_mid = preg_replace('/[^a-z0-9_]+/', '_', $menu_id);
    \Drupal::moduleHandler()->alter(['sitemap_menu_tree', 'sitemap_menu_tree_' . $alter_mid], $tree, $menu);

    $menu_display = \Drupal::service('menu.link_tree')->buildForSitemap($tree);

    $attributes = new Attribute();
    $attributes->addClass('sitemap-menu');

    return [
      '#theme' => 'sitemap_item',
      '#title' => $title,
      '#content' => $menu_display,
      '#attributes' => $attributes,
    ];
  }

}
