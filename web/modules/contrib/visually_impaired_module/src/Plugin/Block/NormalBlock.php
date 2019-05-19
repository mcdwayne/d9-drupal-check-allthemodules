<?php

namespace Drupal\visually_impaired_module\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Normal' Block.
 *
 * @Block(
 *   id = "normal_block",
 *   admin_label = @Translation("Normal block"),
 * )
 */
class NormalBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = \Drupal::formBuilder()->getForm('Drupal\visually_impaired_module\Form\VINormalForm');
    return $form;
  }

}
