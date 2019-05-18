<?php

namespace Drupal\commerce_instamojo\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Messenger\Messenger;
use Instamojo\Instamojo;

/**
 * Provides the Off-site Redirect payment gateway for Instamojo.
 *
 * @CommercePaymentGateway(
 *   id = "instamojo_offsite_checkout",
 *   label = @Translation("Instamojo (Redirect to Instamojo)"),
 *   display_label = "Instamojo",
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_instamojo\PluginForm\OffsiteRedirect\PaymentOffsiteForm",
 *   },
 * )
 */
class InstamojoCheckout extends OffsitePaymentGatewayBase {

  const INSTAMOJO_TEST_API_URL = 'https://test.instamojo.com/api/1.1/';
  const INSTAMOJO_LIVE_API_URL = 'https://www.instamojo.com/api/1.1/payment-requests/';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'api_key' => '',
      'auth_token' => '',
      'salt' => '',
      'order_prefix' => '',
      'send_email' => FALSE,
      'allow_repeated_payments' => TRUE,
      'watchdog_log' => FALSE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // API Key.
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Private API Key'),
      '#description' => $this->t('This is the private key from the Instamojo.'),
      '#default_value' => $this->configuration['api_key'],
      '#required' => TRUE,
    ];

    // Auth Token.
    $form['auth_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Private Auth Token'),
      '#description' => $this->t('This is the private auth token from the Instamojo.'),
      '#default_value' => $this->configuration['auth_token'],
      '#required' => TRUE,
    ];

    // Salt.
    $form['salt'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Private Salt'),
      '#description' => $this->t('This is the salt from the Instamojo.'),
      '#default_value' => $this->configuration['salt'],
      '#required' => TRUE,
    ];

    // Order Prefix.
    $form['order_prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Order ID prefix'),
      '#description' => $this->t('Prefix for order IDs.'),
      '#default_value' => $this->configuration['order_prefix'],
    ];

    // Send Mail.
    $form['send_email'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send Email'),
      '#description' => $this->t('If you want to send email to the payer if email is specified then check this checkbox. If email is not specified then an error is raised.'),
      '#default_value' => $this->configuration['send_email'],
    ];

    // Allow Repeated Payments.
    $form['allow_repeated_payments'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow Repeated Payments'),
      '#description' => $this->t('If this is checked then the link is not accessible publicly after first successful payment'),
      '#default_value' => $this->configuration['allow_repeated_payments'],
    ];

    // Watchdog Log.
    $form['watchdog_log'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Watchdog Log'),
      '#description' => $this->t('Log status to watchdog.'),
      '#default_value' => $this->configuration['watchdog_log'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['api_key'] = $values['api_key'];
      $this->configuration['auth_token'] = $values['auth_token'];
      $this->configuration['salt'] = $values['salt'];
      $this->configuration['send_email'] = $values['send_email'];
      $this->configuration['allow_repeated_payments'] = $values['allow_repeated_payments'];
      $this->configuration['watchdog_log'] = $values['watchdog_log'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {

    $config = $this->getConfiguration();

    $api = new Instamojo($config['api_key'], $config['auth_token'],
      $config['mode'] == 'test' ? self::INSTAMOJO_TEST_API_URL : self::INSTAMOJO_LIVE_API_URL);

    $payment_id = $request->get('payment_id');
    $payment_status = $request->get('payment_status');
    $payment_request_id = $request->get('payment_request_id');

    try {
      $response = $api->paymentRequestPaymentStatus($payment_request_id, $payment_id);
      $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
      $payment = $payment_storage->create([
        'state' => $response['status'],
        'amount' => $order->getTotalPrice(),
        'payment_gateway' => $this->entityId,
        'order_id' => $order->id(),
        'test' => $this->getMode() == 'test',
        'remote_id' => $payment_request_id,
        'remote_state' => $payment_status,
        'authorized' => $this->time->getRequestTime(),
      ]);
      $payment->save();

      $messenger = \Drupal::messenger();
      $messenger->addMessage($this->t('Your payment was successful with Order id : @orderid and Transaction id : @transaction_id',
          [
            '@orderid' => $order->id(),
            '@transaction_id' => $payment_request_id,
          ]
        ),
        $messenger::TYPE_STATUS
      );
      if ($config['watchdog_log']) {
        \Drupal::logger('commerce_instamojo_log')
          ->info('Your payment was successful with Order id : @orderid and Transaction id : @transaction_id',
            [
              '@orderid' => $order->id(),
              '@transaction_id' => $payment_request_id,
            ]
          );
      }
    }
    catch (Exception $e) {
      $messenger = \Drupal::messenger();
      $messenger->addMessage($e->getMessage(), $messenger::TYPE_ERROR);
      if ($config['watchdog_log']) {
        \Drupal::logger('commerce_instamojo_log')->error($e->getMessage());
      }
    }
  }

}
