<?php

namespace Drupal\stripe_examples\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Simple checkout' Block.
 *
 * @Block(
 *   id = "stripe_example_checkout",
 *   admin_label = @Translation("Stripe simple checkout"),
 * )
 */
class SimpleCheckoutBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = \Drupal::formBuilder()->getForm('\Drupal\stripe_examples\Form\SimpleCheckoutForm');
    return $form;
  }

}
