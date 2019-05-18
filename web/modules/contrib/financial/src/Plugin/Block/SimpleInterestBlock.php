<?php

namespace Drupal\financial\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a SMS Sending Form Block.
 *
 * @Block(
 *   id = "Simple_Interest",
 *   admin_label = @Translation("Simple Interest Block")
 * )
 */
class SimpleInterestBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    $form_class = '\Drupal\financial\Form\SimpleInterestForm';
    $block['form'] = \Drupal::formBuilder()->getForm($form_class);
    $formof = \Drupal::service('renderer')->render($block['form']);
    return [
      '#type' => 'markup',
      '#markup' => $formof,
    ];
  }

}
