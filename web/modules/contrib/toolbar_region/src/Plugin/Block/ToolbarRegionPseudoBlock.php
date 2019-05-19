<?php

namespace Drupal\toolbar_region\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a generic Toolbar region pseudo block.
 *
 * @Block(
 *   id = "toolbar_region_pseudo_block",
 *   admin_label = @Translation("Toolbar region"),
 *   category = @Translation("Toolbar regions"),
 *   deriver =
 *   "Drupal\toolbar_region\Plugin\Derivative\ToolbarRegionPseudoBlock"
 * )
 */
class ToolbarRegionPseudoBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
  }
}
