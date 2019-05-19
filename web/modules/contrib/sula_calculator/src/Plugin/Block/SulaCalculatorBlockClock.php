<?php

namespace Drupal\sula_calculator\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'SULA Calculator' Block.
 *
 *  *  * @Block(
 *   id = "sula_calculator_clock",
 *   admin_label = @Translation("SULA Calculator - Clock"),
 *   category = @Translation("SULA Estimator")
 *   )
 */
class SulaCalculatorBlockClock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = \Drupal::formBuilder()->getForm('Drupal\sula_calculator\Form\SulaCalculatorBlockFormClock');
    return $form;
  }

}
