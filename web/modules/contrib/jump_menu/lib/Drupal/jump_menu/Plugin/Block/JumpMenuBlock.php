<?php

/**
 * @file
 * Contains \Drupal\jump_menu\Plugin\Block\JumpMenuBlock.
 */

namespace Drupal\jump_menu\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\block\Annotation\Block;
use Drupal\Core\Annotation\Translation;

/**
 * Provides a Jump Menu block.
 *
 * @Block(
 *   id = "jump_menu_block",
 *   admin_label = @Translation("Jump Menu"),
 *   category = @Translation("Jump Menu"),
 *   derivative = "Drupal\jump_menu\Plugin\Derivative\JumpMenuBlock"
 * )
 */
class JumpMenuBlock extends BlockBase {

  /**
   * Overrides \Drupal\block\BlockBase::access().
   */
  public function access() {
    // @todo Clean up when http://drupal.org/node/1874498 lands.
    list( , $derivative) = explode(':', $this->getPluginId());
    return ($GLOBALS['user']->isAuthenticated() || in_array($derivative, array('main', 'tools', 'footer')));
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // @todo Clean up when http://drupal.org/node/1874498 lands.
    //list(, $menu) = explode(':', $this->getPluginId());

    return 'THE MENU';
  }

}
