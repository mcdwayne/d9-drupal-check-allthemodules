<?php

/**
 * Provides a Subscribe block for Flexmail.
 *
 * @Block(
 *   id = "flexmail_subscribe",
 *   admin_label = @Translation("Flexmail subscribe block"),
 * )
 */

namespace Drupal\flexmail\Plugin\Block;

use Drupal\Core\Block\BlockBase;

class SubscribeBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
    return \Drupal::formBuilder()
      ->getForm(\Drupal\flexmail\Form\SubscribeForm::class);
  }
}

?>