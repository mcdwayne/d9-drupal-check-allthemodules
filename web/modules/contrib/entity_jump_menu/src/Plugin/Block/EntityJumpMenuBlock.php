<?php

/**
 * @file
 * Contains \Drupal\entity_jump_menu\Plugin\Block\EntityJumpMenuBlock.
 */

namespace Drupal\entity_jump_menu\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'EntityJumpMenuBlock' block.
 *
 * @Block(
 *  id = "entity_jump_menu",
 *  admin_label = @Translation("Entity jump menu"),
 *   category = @Translation("Forms")
 * )
 */
class EntityJumpMenuBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return \Drupal::formBuilder()->getForm('Drupal\entity_jump_menu\Form\EntityJumpMenuForm');
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // Never cache the block since it contains a form.
    return 0;
  }

}
