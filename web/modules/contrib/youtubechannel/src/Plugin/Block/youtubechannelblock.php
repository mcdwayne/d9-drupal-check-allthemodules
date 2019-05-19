<?php

/**
 *
 * @Block(
 *   id = "youtubechannel_block",
 *   admin_label = @Translation("Youtube Channel"),
 * )
 */
namespace Drupal\youtubechannel\Plugin\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;

class Youtubechannelblock extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
    return array(
      '#theme' => 'youtubechannel_block',
    );
  }
}
?>

 
