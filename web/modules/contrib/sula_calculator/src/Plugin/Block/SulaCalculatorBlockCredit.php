<?php

namespace Drupal\sula_calculator\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'SULA Calculator' Block.
 *
 *  *  * @Block(
 *   id = "sula_calculator_credit",
 *   admin_label = @Translation("SULA Calculator - Credit"),
 *   category = @Translation("SULA Estimator")
 *   )
 */
class SulaCalculatorBlockCredit extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = \Drupal::formBuilder()->getForm('Drupal\sula_calculator\Form\SulaCalculatorBlockFormCredit');
    return $form;
  }

}
