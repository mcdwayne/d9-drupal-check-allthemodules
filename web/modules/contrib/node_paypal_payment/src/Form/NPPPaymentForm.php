<?php

namespace Drupal\node_paypal_payment\Form;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides the PayPal Standard payment form.
 */
class NPPPaymentForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'npp_payment_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $token = NULL) {
    // Clear all messages.
    drupal_get_messages();

    $return_url = $this->url('node_paypal_payment.redirect.success', ['token' => $token], ['absolute' => TRUE]);
    $cancel_url = $this->url('node_paypal_payment.redirect.cancel', ['token' => $token], ['absolute' => TRUE]);
    $ipn_url = $this->url('node_paypal_payment.notify', ['token' => $token], ['absolute' => TRUE]);

    $payment = db_select('npp_payments', 'p')
      ->fields('p', ['id', 'entity_id'])
      ->condition('p.token', $token)
      ->execute()
      ->fetchObject();

    if (!$payment) {
      // AccessDeniedHttpException.
      throw new NotFoundHttpException();
    }

    $entity_id = $payment->entity_id;
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($entity_id);

    $config = $this->config('node_paypal_payment.settings');

    $data = [

      // The store's PayPal e-mail address.
      'business' => $config->get('npp_email'),

      // Specify the checkout experience to present to the user.
      'cmd' => '_xclick',

      // The path PayPal should send the IPN to.
      'notify_url' => $ipn_url,

      // The application generating the API request.
      'bn' => 'NodePayPalPayment_PPS',

      // Set the correct character set.
      'charset' => 'utf-8',

      // Do not display a comments prompt at PayPal.
      'no_note' => 1,

      // Do not display a shipping address prompt at PayPal.
      'no_shipping' => 1,

      // Return to the review page when payment is canceled.
      'cancel_return' => $cancel_url,

      // Return to the payment redirect page for processing successful payments.
      'return' => $return_url,

      // Return to this site with payment data in the POST.
      'rm' => 2,

      // The type of payment action PayPal should take with this order
      // Ex.(sale, authorization, order)
      'paymentaction' => 'sale',

      // Set the currency and language codes.
      'currency_code' => $config->get('npp_currency'),

      // Use the timestamp to generate a unique invoice number.
      'invoice' => time(),

      // Define a single item in the cart representing the whole order.
      'amount' => $config->get('npp_amount'),

      // Title of the content.
      'item_name' => $node->getTitle(),

      // Id of the content.
      'item_number' => $entity_id,
    ];

    $payment_mode = $config->get('npp_mode') ? $config->get('npp_mode') : 'sandbox';
    $form['#action'] = node_paypal_payment_paypal_wps_server_url($payment_mode);

    foreach ($data as $name => $value) {
      $form[$name] = ['#type' => 'hidden', '#value' => $value];
    }

    $payment_summary = [
      '#theme' => 'npp_payment_summary',
      '#data' => $data,
    ];

    $form['summary'] = [
      '#markup' => \Drupal::service('renderer')->render($payment_summary),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Pay Now'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
