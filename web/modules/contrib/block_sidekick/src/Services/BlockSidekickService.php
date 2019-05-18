<?php

namespace Drupal\block_sidekick\Services;

use Drupal\Core\Controller\ControllerBase;

/**
 * Services provided by the Block Sidekick module.
 */
class BlockSidekickService extends ControllerBase {

  /**
   * Gets the currently active non-administrative theme.
   *
   * @return array
   *   An array of theme properties.
   */
  public function getTheme() {
    $theme = [];
    $themeObj = \Drupal::theme()->getActiveTheme();
    $theme['name'] = $themeObj->getName();
    $theme['regions'] = $themeObj->getRegions();
    return $theme;
  }

  /**
   * Gets all blocks that are available to be used on the page.
   *
   * @return array
   *   An array of blocks.
   */
  public function getBlocks() {
    $blockManager = \Drupal::service('plugin.manager.block');
    $blocks = $blockManager->getGroupedDefinitions();
    return $blocks;
  }

  /**
   * Get the regions available to the theme as element ids for jQuery selection.
   *
   * @param string $theme
   *   The theme name.
   *
   * @return array
   *   An array of regions.
   */
  public function getRegionElementIds($theme) {
    $regions = [];
    $regionsRaw = system_region_list($theme, REGIONS_VISIBLE);
    foreach ($regionsRaw as $regionId => $regionName) {
      $regions[] = str_replace("_", "-", $regionId);
    }
    return $regions;
  }

}
