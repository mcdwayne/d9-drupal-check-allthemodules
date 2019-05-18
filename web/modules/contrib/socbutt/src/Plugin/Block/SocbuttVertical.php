<?php
/**
 * @file
 * Contains \Drupal\socbutt\Plugin\Block\SocbuttVertical.
 */

namespace Drupal\socbutt\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Url;

/**
 * Provides my custom block.
 *
 * @Block(
 *   id = "socbutt_vertic_block",
 *   admin_label = @Translation("SocbuttonsVertical"),
 *   category = @Translation("Blocks")
 * )
 */
class SocbuttVertical extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return _socbutt_buttons_render(array('layout' => 0));
  }

}
