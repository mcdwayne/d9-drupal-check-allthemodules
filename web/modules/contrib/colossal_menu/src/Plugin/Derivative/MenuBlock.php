<?php

/**
 * @file
 * Contains \Drupal\colossal_menu\Plugin\Derivative\MenuBlock.
 */

namespace Drupal\colossal_menu\Plugin\Derivative;

use Drupal\system\Plugin\Derivative\SystemMenuBlock;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides block plugin definitions for custom menus.
 *
 * @see \Drupal\colossal_menu\Plugin\Block\MenuBlock
 */
class MenuBlock extends SystemMenuBlock {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity.manager')->getStorage('colossal_menu')
    );
  }

}
