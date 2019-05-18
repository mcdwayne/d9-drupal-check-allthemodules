<?php

namespace Drupal\micro_simple_sitemap\Plugin\simple_sitemap\UrlGenerator;

use Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator\EntityMenuLinkContentUrlGenerator;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\system\MenuInterface;
use Drupal\Core\Menu\MenuLinkTree;

/**
 * Class EntityMenuLinkContentUrlGenerator.
 *
 * @package Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator
 *
 * @UrlGenerator(
 *   id = "micro_site_entity_menu_link_content",
 *   label = @Translation("Menu link URL generator"),
 *   description = @Translation("Generates menu link URLs by overriding the 'entity' URL generator for menu attached to a micro site."),
 *   settings = {
 *     "overrides_entity_type" = "menu_link_content",
 *   },
 * )
 */
class MicroSiteEntityMenuLinkContentUrlGenerator extends EntityMenuLinkContentUrlGenerator {

  use MicroSiteUrlGeneratorTrait;

  /**
   * @inheritdoc
   */
  public function getDataSets() {
    $data_sets = [];
    $variant_site_id = $this->getSiteId($this->sitemapVariant);
    $bundles_settings = $this->generator
      ->setVariants($this->sitemapVariant)
      ->getBundleSettings();
    if (!empty($bundles_settings['menu_link_content'])) {
      foreach ($bundles_settings['menu_link_content'] as $bundle_name => $bundle_settings) {
        $menu = $this->entityTypeManager->getStorage('menu')->load($bundle_name);
        if ($menu instanceof MenuInterface) {
          $site_id = $menu->getThirdPartySetting('micro_menu', 'site_id');
          if (empty($site_id)) {
            continue;
          }
          if ($site_id != $variant_site_id) {
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
