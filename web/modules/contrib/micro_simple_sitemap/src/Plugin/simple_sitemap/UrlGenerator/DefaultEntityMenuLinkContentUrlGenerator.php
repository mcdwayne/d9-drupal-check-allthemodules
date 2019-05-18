<?php

namespace Drupal\micro_simple_sitemap\Plugin\simple_sitemap\UrlGenerator;

use Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator\EntityMenuLinkContentUrlGenerator;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\system\Entity\Menu;
use Drupal\system\MenuInterface;
use Drupal\Core\Menu\MenuLinkTree;

/**
 * Class DefaultEntityMenuLinkContentUrlGenerator.
 *
 * Extends the default entity menu link content url generator used for master
 * host.
 */
class DefaultEntityMenuLinkContentUrlGenerator extends EntityMenuLinkContentUrlGenerator {

  /**
   * @inheritdoc
   */
  public function getDataSets() {
    $data_sets = [];
    $bundle_settings = $this->generator
      ->setVariants($this->sitemapVariant)
      ->getBundleSettings();
    if (!empty($bundle_settings['menu_link_content'])) {
      foreach ($bundle_settings['menu_link_content'] as $bundle_name => $bundle_settings) {
        // If the menu is attached to a micro site, do not index it.
        $menu = Menu::load($bundle_name);
        if ($menu instanceof MenuInterface) {
          $site_id = $menu->getThirdPartySetting('micro_menu', 'site_id');
          if ($site_id) {
            continue;
          }
        }

        if (!empty($bundle_settings['index'])) {

          // Retrieve the expanded tree.
          $tree = $this->menuLinkTree->load($bundle_name, new MenuTreeParameters());
          $tree = $this->menuLinkTree->transform($tree, [
            ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
            ['callable' => 'menu.default_tree_manipulators:flatten'],
          ]);

          foreach ($tree as $i => $item) {
            $data_sets[] = $item->link;
          }
        }
      }
    }

    return $data_sets;
  }

}
