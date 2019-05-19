<?php

namespace Drupal\social_sharbar\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'social share' Block.
 *
 * @Block(
 *   id = "social_share_block",
 *   admin_label = @Translation("Social Sharebar"),
 *   category = @Translation("Social Sharebar"),
 * )
 */
class SocialSharBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return ['#theme' => 'social_sharbar'];
  }

}
