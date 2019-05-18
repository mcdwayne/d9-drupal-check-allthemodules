<?php
/**
 * @file
 * Contains \Drupal\jump_menu\Plugin\Block\JumpMenuLocalTasksBlock.
 */

namespace Drupal\jump_menu\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\block\Annotation\Block;
use Drupal\Core\Annotation\Translation;

/**
 * Provides a "Who's online" block.
 *
 * @Block(
 *   id = "jump_menu_local_tasks",
 *   admin_label = @Translation("Local Tasks Jump Menu")
 * )
 */
class JumpMenuLocalTasksBlock extends BlockBase {

  /**
   * Implements \Drupal\block\BlockBase::build().
   */
  public function build() {

    return 'Local Tasks Jump Menu';

  }

}
