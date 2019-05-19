<?php

namespace Drupal\visually_impaired_module\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Visually Impaired' Block.
 *
 * @Block(
 *   id = "visually_impaired_block",
 *   admin_label = @Translation("Visually Impaired block"),
 * )
 */
class VIBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = \Drupal::formBuilder()->getForm('Drupal\visually_impaired_module\Form\VISpecialForm');
    return $form;
  }

}
