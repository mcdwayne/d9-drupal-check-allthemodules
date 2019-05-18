<?php

namespace Drupal\msg91\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a SMS Sending Form Block.
 *
 * @Block(
 *   id = "MSG91_SMSBlock",
 *   admin_label = @Translation("SMS Sending Form")
 * )
 */
class MSG91_SMSBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    $form_class = '\Drupal\msg91\Form\SMSSendingForm';
    $block['form'] = \Drupal::formBuilder()->getForm($form_class);
    $formof = \Drupal::service('renderer')->render($block['form']);
    return [
      '#type' => 'markup',
      '#markup' => $formof,
    ];
  }

}
