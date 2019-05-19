<?php

/**
 * @file
 * Contains \Drupal\sjisocialconnect\Plugin\Block\SjiSocialConnectBlock.
 */

namespace Drupal\sjisocialconnect\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Provides an Sji Social Connectblock.
 *
 * @Block(
 *   id = "sjisocialconnect_block",
 *   admin_label = @Translation("Sji Social Connect"),
 * )
 */
class SjiSocialConnectBlock extends BlockBase {

  /**
   * Implements \Drupal\Block\BlockBase::blockBuild().
   */
  public function build() {
    $content = array(
      '#theme' => 'sjisocialconnect',
    );

    return array(
      '#children' => \Drupal::service('renderer')->render($content),
    );
  }

}
