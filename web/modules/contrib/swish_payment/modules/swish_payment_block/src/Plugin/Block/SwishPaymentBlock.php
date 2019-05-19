<?php

namespace Drupal\swish_payment_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'SwishPaymentBlock' block.
 *
 * @Block(
 *  id = "swish_payment_block",
 *  admin_label = @Translation("Swish payment block"),
 * )
 */
class SwishPaymentBlock extends BlockBase {


  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
         'amount' => 0,
        ] + parent::defaultConfiguration();

 }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['amount'] = [
      '#type' => 'number',
      '#title' => $this->t('Amount'),
      '#description' => $this->t('Enter the amount in SEK'),
      '#default_value' => $this->configuration['amount'],
      '#weight' => '1',
    ];
    $form['ref'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Payment reference'),
      '#description' => $this->t('Enter payment reference here.'),
      '#weight' => '1',
    ];
    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message'),
      '#description' => $this->t('Enter a messsage here.'),
      '#weight' => '1',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['amount'] = $form_state->getValue('amount');
    $this->configuration['ref'] = $form_state->getValue('ref');
    $this->configuration['message'] = $form_state->getValue('message');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $options = ['amount' =>$this->configuration['amount'], 'reference' => $this->configuration['ref'], 'message' => $this->configuration['message']];
    $form = \Drupal::formBuilder()->getForm('Drupal\swish_payment_block\Form\SwishPaymentBlockForm', $options);
    $build = [];
    $build['swish_payment_block_amount']['#markup'] = '<p>' . $this->configuration['amount'] . '</p>';
    $build['swish_payment_block_form'] = $form;

    return $build;
  }

}
