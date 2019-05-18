<?php

/**
 * @file
 * Contains \Drupal\colossal_menu\Plugin\Block\MenuBlock.
 */

namespace Drupal\colossal_menu\Plugin\Block;

use Drupal\system\Plugin\Block\SystemMenuBlock;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a generic Colossal Menu block.
 *
 * @Block(
 *   id = "colossal_menu_block",
 *   admin_label = @Translation("Colossal Menu"),
 *   category = @Translation("Colossal Menus"),
 *   deriver = "Drupal\colossal_menu\Plugin\Derivative\MenuBlock"
 * )
 */
class MenuBlock extends SystemMenuBlock {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('colossal_menu.link_tree'),
      $container->get('menu.active_trail')
    );
  }

}
