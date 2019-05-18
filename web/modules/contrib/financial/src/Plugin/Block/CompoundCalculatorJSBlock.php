<?php

namespace Drupal\financial\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\financial\Form\CompoundCalculatorJSForm;

/**
 * Provides a 'Example: uppercase this please' block.
 *
 * @Block(
 *  id = "compound_calculator_js_block",
 *  admin_label = @Translation("Compound Interest JS")
 * )
 */
class CompoundCalculatorJSBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $defaultForm = new CompoundCalculatorJSForm();
    $form = \Drupal::formBuilder()->getForm($defaultForm);
    return $form;
  }

}
