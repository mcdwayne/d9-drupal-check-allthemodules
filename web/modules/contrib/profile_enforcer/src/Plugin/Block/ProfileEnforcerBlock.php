<?php

namespace Drupal\profile_enforcer\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'ProfileEnforcerBlock' block.
 *
 * @Block(
 *  id = "profile_enforcer_block",
 *  admin_label = @Translation("Profile enforcer block"),
 * )
 */
class ProfileEnforcerBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['profile_enforcer_block']['#markup'] = '';

    return $build;
  }

}
