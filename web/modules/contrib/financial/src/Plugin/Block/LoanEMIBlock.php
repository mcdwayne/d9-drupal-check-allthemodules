<?php

namespace Drupal\financial\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a Loan EMI Form Block.
 *
 * @Block(
 *   id = "Loan_EMI",
 *   admin_label = @Translation("Loan EMI Block")
 * )
 */
class LoanEMIBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    $form_class = '\Drupal\financial\Form\LoanEMIForm';
    $block['form'] = \Drupal::formBuilder()->getForm($form_class);
    $formof = \Drupal::service('renderer')->render($block['form']);
    return [
      '#type' => 'markup',
      '#markup' => $formof,
    ];
  }

}
