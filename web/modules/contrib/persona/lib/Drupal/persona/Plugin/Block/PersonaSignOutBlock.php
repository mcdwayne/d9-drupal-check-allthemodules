<?php

/**
 * @file
 * Contains \Drupal\persona\PlugOut\Block\PersonaSignOutBlock.
 */

namespace Drupal\persona\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\block\Annotation\Block;
use Drupal\Core\Annotation\Translation;

/**
 * Provides a Persona Sign Out block.
 *
 * @Block(
 *   id = "persona_sign_out_block",
 *   admin_label = @Translation("Persona Sign Out button")
 * )
 */
class PersonaSignOutBlock extends BlockBase {

  /**
   * Implements \Drupal\block\BlockBase::build().
   */
  public function build() {
    return array('#theme' => 'persona_button', '#type' => 'sign-out');
  }

}
