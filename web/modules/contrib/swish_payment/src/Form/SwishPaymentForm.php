<?php

namespace Drupal\swish_payment\Form;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\swish_payment\SwishClient;

/**
 * Class SwishPaymentForm.
 *
 * @package Drupal\swish_payment\Form
 */
class SwishPaymentForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'swish_payment_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['phone'] = [
      '#type' => 'tel',
      '#title' => $this->t('Cell number'),
      '#required' => true,
      '#description' => $this->t('Enter the phone number of payment device here.'),
    ];
    $form['amount'] = [
      '#type' => 'number',
      '#title' => $this->t('Amount'),
      '#required' => true,
      '#description' => $this->t('Enter amount to pay/donate here.'),
    ];
    $form['ref'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Payment reference'),
      '#description' => $this->t('Enter payment reference here.'),
    ];
    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message'),
      '#description' => $this->t('Enter a messsage here.'),
    ];
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
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $client = SwishClient::create();
    $trans = $client->createPaymentRequest(
        $form_state->getValue('phone'),
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
