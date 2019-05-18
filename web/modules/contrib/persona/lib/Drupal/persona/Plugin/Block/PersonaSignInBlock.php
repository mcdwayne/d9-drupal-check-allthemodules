<?php

/**
 * @file
 * Contains \Drupal\persona\Plugin\Block\PersonaSignInBlock.
 */

namespace Drupal\persona\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\block\Annotation\Block;
use Drupal\Core\Annotation\Translation;

/**
 * Provides a Persona Sign In block.
 *
 * @Block(
 *   id = "persona_sign_in_block",
 *   admin_label = @Translation("Persona Sign In button")
 * )
 */
class PersonaSignInBlock extends BlockBase {

  /**
   * Implements \Drupal\block\BlockBase::build().
   */
  public function build() {
    return array('#theme' => 'persona_button', '#type' => 'sign-in');
  }

}
