<?php

/**
 * @file
 * Contains \Drupal\persona\Plugin\Block\PersonaChangeEmailBlock.
 */

namespace Drupal\persona\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\block\Annotation\Block;
use Drupal\Core\Annotation\Translation;

/**
 * Provides a Persona Change Email block.
 *
 * @Block(
 *   id = "persona_change_email_block",
 *   admin_label = @Translation("Persona Change Email button")
 * )
 */
class PersonaChangeEmailBlock extends BlockBase {

  /**
   * Implements \Drupal\block\BlockBase::build().
   */
  public function build() {
    return array('#theme' => 'persona_button', '#type' => 'change-email');
  }

}
