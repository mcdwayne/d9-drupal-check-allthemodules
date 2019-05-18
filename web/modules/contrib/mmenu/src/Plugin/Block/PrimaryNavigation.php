<?php

namespace Drupal\mmenu\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a Primary Navigation block.
 *
 * @Block(
 *  id = "mmenu:primary_navigation",
 *  admin_label = @Translation("Primary Navigation"),
 * )
 */
class PrimaryNavigation extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $render_array = array(
      '#theme' => 'mmenu_primary_navigation',
    );

    $block = array(
      '#type' => 'markup',
      '#markup' => render($render_array),
    );
    return $block;
  }

}
