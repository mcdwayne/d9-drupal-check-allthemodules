<?php

/**
 * @file
 * Contains \Drupal\donation_button\Plugin\Block\donation_buttonButtonBlock.
 */
namespace Drupal\donation_button\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'donation_button button' block.
 *
 * @Block(
 *   id = "donation_button_block",
 *   admin_label = @Translation("Donation button block"),
 *   category = @Translation("Custom donation button block example")
 * )
 */

class DonationButtonBlock extends BlockBase {
  
  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = \Drupal::formBuilder()->getForm('Drupal\donation_button\Form\DonationButtonForm');
    return $form;
  }
}

