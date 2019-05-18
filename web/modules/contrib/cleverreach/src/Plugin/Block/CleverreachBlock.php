<?php

/**
 * @file
 * Contains \Drupal\cleverreach\Plugin\Block\CleverreachBlock.
 */

namespace Drupal\cleverreach\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Cleverreach subscribe form' block.
 *
 * @Block(
 *   id = "subscribe_block",
 *   admin_label = @Translation("Cleverreach: Subscribe block"),
 *   category = @Translation("Cleverreach"),
 *   deriver = "Drupal\cleverreach\Plugin\Derivative\CleverreachBlock"
 * )
 */
class CleverreachBlock extends BlockBase {
  
  public function build() {
    $block_id = $this->getDerivativeId();
    $form_object = new \Drupal\cleverreach\Form\CleverreachBlockForm($block_id);
    $form = \Drupal::formBuilder()->getForm($form_object);
    return $form;
  }
  
}