<?php

namespace Drupal\helpfulness\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;

/**
 * Provides a 'Helpfulness' block.
 *
 * @Block(
 *   id = "helpfulness_block",
 *   admin_label = @Translation("Helpfulness block"),
 * )
 */
class HelpfulnessBlock extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = \Drupal::formBuilder()->getForm('\Drupal\helpfulness\Form\HelpfulnessBlockForm');

    return $form;
  }

}
