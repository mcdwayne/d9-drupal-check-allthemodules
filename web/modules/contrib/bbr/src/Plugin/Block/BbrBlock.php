<?php

namespace Drupal\bbr\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'bbr form' Block.
 *
 * @Block(
 *   id = "bbr",
 *   admin_label = @Translation("Back Button Refresh"),
 *   category = @Translation("Back Button Refresh"),
 * )
 */
class BbrBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = \Drupal::formBuilder()->getForm('Drupal\bbr\Form\BbrForm');
    return $form;
  }

}
