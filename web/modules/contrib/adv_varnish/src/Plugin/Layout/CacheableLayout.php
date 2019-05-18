<?php

/**
 * @file
 * Contains \Drupal\adv_varnish\Plugin\Layout\CacheableLayout.
 */

namespace Drupal\adv_varnish\Plugin\Layout;

use Drupal\layout_plugin\Plugin\Layout\LayoutBase;
use \Drupal\Core\Render\Element;

/**
 * Cacheable layout plugin..
 */
class CacheableLayout extends LayoutBase {

  /**
   * Build regions.
   */
  public function build(array $regions) {

    foreach (Element::children($regions) as $region_id) {
      $region = &$regions[$region_id];
      foreach (Element::children($region) as $block_id) {
        $block = &$region[$block_id];
        $default = \Drupal::config('adv_varnish.settings');
        if ($block_id) {
          $settings = $default->get($block_id);
        }
        if (!empty($settings['cache']['esi'])) {

          // If we need to replace block with ESI we
          // change #pre_render callback to handle this.
          $block['#theme'] = 'adv_varnish_esi_block';
          $block['#pre_render'] = ['_adv_varnish_build_panels_esi_block'];
        }
      }
    }
    return parent::build($regions);
  }

}
