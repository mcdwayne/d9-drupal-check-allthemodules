<?php

namespace Drupal\social_share_counter\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides an 'Social Share Counter' block.
 *
 * @Block(
 *   id = "ssc_block",
 *   admin_label = @Translation("Social Share Counter buttons"),
 * )
 */
class SocialShareCounterBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = \Drupal::routeMatch()->getParameter('node');

    return array(
      '#markup' => _ssc_create_button($node),
      '#attached' => array(
          'library' => array(
            'social_share_counter/social_share_counter',
          ),
        ),
    );
  }

}
