<?php
/**
 * @file
 * Contains \Drupal\jiathis\Plugin\Block\jiathis.
 */
namespace Drupal\jiathis\Plugin\Block;
use Drupal\Core\Block\BlockBase;

/**
 * Provides form block.
 *
 * @Block(
 *   id = "JiaThis",
 *   admin_label = @Translation("JiaThis Sharebutton"),
 *   category = @Translation("JiaThis")
 * )
 */
class JiaThis extends BlockBase {
  /**
  * {@inheritdoc}
  */
  public function build() {
      return  _generate__jia_block_button();
  }
}
