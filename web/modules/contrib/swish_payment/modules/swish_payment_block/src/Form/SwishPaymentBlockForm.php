<?php

namespace Drupal\swish_payment_block\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\swish_payment\SwishClient;
use Symfony\Component\HttpFoundation\RedirectResponse;


/**
 * Class SwishPaymentBlockForm.
 *
 * @package Drupal\swish_payment_block\Form
 */
class SwishPaymentBlockForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'swish_payment_block_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $options = null) {
    $form['cellphone_number'] = [
      '#type' => 'tel',
      '#title' => $this->t('Cellphone number'),
      '#description' => $this->t('Enter cellphone number to the device where Swish is installed'),
    ];

    if(is_array($options) && is_numeric($options['amount']) && $options['amount'] > 0) {
      $form['amount'] = [
        '#type' => 'hidden',
        '#value' => $options['amount'],
      ];
    }
    else {
      $form['amount'] = [
        '#type' => 'number',
        '#title' => $this->t('Amount'),
        '#required' => true,
        '#description' => $this->t('Enter amount to pay/donate here.'),
      ];
    }

    if(is_array($options) && !empty($options['reference'])) {
      $form['ref'] = [
        '#type' => 'hidden',
        '#value' => $options['reference'],
      ];
    }
    else {
      $form['ref'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Payment reference'),
        '#description' => $this->t('Enter payment reference here.'),
      ];
    }

    if(is_array($options) && !empty($options['message'])) {
      $form['message'] = [
        '#type' => 'hidden',
        '#value' => $options['message'],
      ];
    }

    $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Submit'),
    ];
    return $form;
  }

  /**
    * {@inheritdoc}
    */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if(!SwishClient::validatePhoneNo($form_state->getValue('cellphone_number')))
      $form_state->setErrorByName('cellphone_number', t('Enter a valid phone no.'));
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $client = SwishClient::create();
    $trans = $client->createPaymentRequest(
        $form_state->getValue('cellphone_number'),
        $form_state->getValue('amount'),
        $form_state->getValue('ref'),
        $form_state->getValue('message')
      );
    $form_state->disableRedirect();
    if($trans) {
      $form_state->setResponse(
        new RedirectResponse(
          Url::fromRoute("swish_payment.callback_pending", ['trans_id' => $trans->getTransactionId()], ['https'=>TRUE, 'absolute'=>TRUE] )->toString())
        );
    }
    else {
      $form_state->setResponse(
        new RedirectResponse(
          Url::fromRoute("swish_payment.callback_error", [], ['absolute'=>TRUE] )->toString())
        );
    }
  }
}
