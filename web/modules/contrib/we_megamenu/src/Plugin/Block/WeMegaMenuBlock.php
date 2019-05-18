<?php

namespace Drupal\we_megamenu\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\we_megamenu\WeMegaMenuBuilder;

/**
 * Provides a 'Drupal 8 Mega Menu' Block.
 *
 * @Block(
 *   id = "we_megamenu_block",
 *   admin_label = @Translation("Drupal 8 Mega Menu"),
 *   category = @Translation("Drupal 8 Mega Menu"),
 *   deriver = "Drupal\we_megamenu\Plugin\Derivative\WeMegaMenuBlock",
 * )
 */
class WeMegaMenuBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [
      '#theme' => 'we_megamenu_frontend',
      '#menu_name' => $this->getDerivativeId(),
      '#blocks' => WeMegaMenuBuilder::getAllBlocks(),
      '#block_theme' => \Drupal::config('system.theme')->get('default'),
      '#attached' => [
        'library' => [
          'we_megamenu/form.we-mega-menu-frontend',
        ],
      ],
    ];
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['label_display' => FALSE];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $menu_name = $this->getDerivativeId();
    $id_menu = 'config:system.menu.' . $menu_name;
    $ids = [$id_menu];
    return Cache::mergeTags(parent::getCacheTags(), $ids);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $menu_name = $this->getDerivativeId();
    $id_menu = 'route.menu_active_trails:' . $menu_name;
    $ids = [$id_menu];
    return Cache::mergeContexts(parent::getCacheContexts(), $ids);
  }
}
