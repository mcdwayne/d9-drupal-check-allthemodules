<?php

/**
 *
 * @Block(
 *   id = "youtubechannelpagination_block",
 *   admin_label = @Translation("Youtube Channel Pagination"),
 * )
 */
namespace Drupal\youtubechannelpagination\Plugin\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;

class Youtubechannelpaginationblock extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
    return array(
      '#theme' => 'youtubechannelpagination_block',
    );
  }
}
?>

 
