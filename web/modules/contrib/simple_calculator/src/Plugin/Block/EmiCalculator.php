<?php

/**
 * @file
 * Contains \Drupal\simplecalculator\Plugin\Block\EmicalculatorBlock.
 */

namespace Drupal\simple_calculator\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'emicalculator' block.
 *
 * @Block(
 *   id = "emicalculator_block",
 *   admin_label = @Translation("EMI Calculator"),
 *   category = @Translation("Emicalculator")
 * )
 */
class EmiCalculator extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = \Drupal::formBuilder()->getForm('Drupal\simple_calculator\Form\EmiCalculatorDisplayForm');
    return $form;
  }

}
