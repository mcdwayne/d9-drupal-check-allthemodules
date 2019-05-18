<?php
/**
 * @file
 * Contains \Drupal\socbutt\Plugin\Block\SocbuttHorizont.
 */

namespace Drupal\socbutt\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Url;

/**
 * Provides my custom block.
 *
 * @Block(
 *   id = "socbutt__horiz_block",
 *   admin_label = @Translation("SocbuttonsHorizontal"),
 *   category = @Translation("Blocks")
 * )
 */
class SocbuttHorizont extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return _socbutt_buttons_render(array('layout' => 1));
  }

}
