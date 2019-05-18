<?php

namespace Drupal\financial\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a Compound Interest Form Block.
 *
 * @Block(
 *   id = "compound_interest",
 *   admin_label = @Translation("Compound Interest Block")
 * )
 */
class CompoundInterestBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    $form_class = '\Drupal\financial\Form\CompoundInterestForm';
    $block['form'] = \Drupal::formBuilder()->getForm($form_class);
    $formof = \Drupal::service('renderer')->render($block['form']);
    return [
      '#type' => 'markup',
      '#markup' => $formof,
    ];
  }

}
