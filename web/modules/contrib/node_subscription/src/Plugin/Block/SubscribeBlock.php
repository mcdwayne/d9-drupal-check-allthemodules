<?php

namespace Drupal\node_subscription\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * .
 *
 * Provides a 'Subscribe' block.
 *
 * @Block(
 *   id = "subscribe_block",
 *   admin_label = @Translation("Subscribe Here"),
 *   category = @Translation("Blocks")
 * )
 */
class SubscribeBlock extends BlockBase {
  /**
   * .
   *
   * {@inheritdoc}
   */

  /**
   *
   */
  public function build() {
    $form = \Drupal::formBuilder()->getForm('Drupal\node_subscription\Form\SubscribeForm');
    return $form;
  }

}
