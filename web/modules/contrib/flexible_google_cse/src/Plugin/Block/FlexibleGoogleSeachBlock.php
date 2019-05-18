<?php

namespace Drupal\flexible_google_cse\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'FlexibleGoogleSeachBlock' block.
 *
 * @Block(
 *  id = "flexible_google_seach_block",
 *  admin_label = @Translation("Flexible Google Custom Seach block"),
 * )
 */
class FlexibleGoogleSeachBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Get the Search Form Here.
    $form = \Drupal::formBuilder()
      ->getForm('Drupal\flexible_google_cse\Form\SearchForm');
    return $form;
  }

}
