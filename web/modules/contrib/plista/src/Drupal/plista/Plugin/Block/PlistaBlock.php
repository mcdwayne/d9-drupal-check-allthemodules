<?php
/**
 * @file
 * Contains \Drupal\plista\Plugin\Block\PlistaBlock.
 */
namespace Drupal\plista\Plugin\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\plista\Plista;

/**
 * Provides a simple block.
 *
 * @Block(
 *   id = "plista_block",
 *   admin_label = @Translation("Plista")
 * )
 */
class PlistaBlock extends BlockBase {

  /**
   * Implements \Drupal\block\BlockBase::blockBuild().
   */
  public function build() {

    if ($node = menu_get_object()) {

      $plista = Plista::create($node);
      return $plista->view();
    }

    return array();
  }
}
