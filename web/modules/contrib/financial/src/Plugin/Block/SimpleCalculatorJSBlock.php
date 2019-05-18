<?php

namespace Drupal\financial\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\financial\Form\SimpleCalculatorJSForm;

/**
 * Provides a 'Example: uppercase this please' block.
 *
 * @Block(
 *  id = "simple_calculator_js_block",
 *  admin_label = @Translation("Simple Interest JS")
 * )
 */
class SimpleCalculatorJSBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $defaultForm = new SimpleCalculatorJSForm();
    $form = \Drupal::formBuilder()->getForm($defaultForm);
    return $form;
  }

}
