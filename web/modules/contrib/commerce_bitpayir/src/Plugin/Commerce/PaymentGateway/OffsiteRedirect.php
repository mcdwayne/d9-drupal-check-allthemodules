<?php

namespace Drupal\commerce_bitpayir\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\commerce_bitpayir\BitpayGateway;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Provides the Off-site Redirect payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "commerce_bitpayir_redirect",
 *   label = " Bitpay.ir (Off-site redirect)",
 *   display_label = "Iranian bitpay",
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_bitpayir\PluginForm\OffsiteRedirect\PaymentOffsiteForm",
 *   },
 *   payment_method_types = {"credit_card"},
 * )
 */
class OffsiteRedirect extends OffsitePaymentGatewayBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'api_code' => 'adxcv-zzadq-polkjsad-opp13opoz-1sdf455aadzmck1244567',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['api_code'] = [
      '#type' => 'textfield',
      '#title' => t('API Code'),
      '#default_value' => $this->configuration['api_code'],
      '#description' => t('API code which is provided by Bitpay. If you want to use the gateway in the test mode, you can choose this test api code (See the gateway documentation): adxcv-zzadq-polkjsad-opp13opoz-1sdf455aadzmck1244567'),
      '#required' => TRUE,
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
      // Save configuration
      $this->configuration['api_code'] = $values['api_code'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    $trans_id = $_POST['trans_id'];
    $id_get = $_POST['id_get'];
    $api = $this->configuration['api_code'];
    // Prevents double spending:
    // If a bad manner user have a successfull transaction and want
    // to have another payment with previous trans_id, we must prevent him/her.
    $query = \Drupal::entityQuery('commerce_payment')
      ->condition('remote_id', $trans_id);
      $payments = $query->execute();
    if(count($payments) > 0) {
      \Drupal::logger('commerce_bitpayir')
        ->error('Bitpayir: Double spending occured on order <a href="@url">%order</a> from ip @ip', [
        '@url' => Url::fromUri('base:/admin/commerce/orders/' . $order->id())->toString(),
        '%order' => $order->id(),
        '@ip' => $order->getIpAddress(),
      ]);
      drupal_set_message('Double spending occured.', 'error');
      /** @var \Drupal\commerce_checkout\Entity\CheckoutFlowInterface $checkout_flow */
      $checkout_flow = $order->checkout_flow->entity;
      $checkout_flow_plugin = $checkout_flow->getPlugin();
      $redirect_step = $checkout_flow_plugin->getPreviousStepId();
      $checkout_flow_plugin->redirectToStep($redirect_step);
    }

    // Checks if we are in debug mode
    if($this->configuration['mode'] == 'test') {
      $url = 'https://bitpay.ir/payment-test/gateway-result-second';
    }
    elseif($this->configuration['mode'] == 'live') {
      $url = 'https://bitpay.ir/payment/gateway-result-second';
    }
    // Result  has been returned from Bitpay.ir
    $result = BitpayGateway::get($url, $api, $trans_id, $id_get);
    // All of states which in Bitpay.ir will return the result.
    $bitpay_result_second_messages = [
      -1 => t('Bitpay: api code is not compatible.'),
      -2 => t('Bitpay: trans_id is not valid.'),
      -3 => t('Bitpay: id_get is not valid.'),
      -4 => t('Bitpay: transaction was not successful or not such a transaction.'),
    ];
    // If $result equals to 1, Transaction is successful
    // and we must save the payment.
    if ($result == '1') {
      $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
      $payment = $payment_storage->create([
        'state' => 'completed',
        'amount' => $order->getTotalPrice(),
        'payment_gateway' => $this->entityId,
        'order_id' => $order->id(),
        'test' => $this->getMode() == 'test',
        'remote_id' => $_POST['trans_id'],
        //'remote_state' => $request->query->get('payment_status'),
        'authorized' => $this->time->getRequestTime(),
      ]);
      $payment->save();
      drupal_set_message('Payment was processed');
    }
    else {
      if ($result <= '-1' && $result >= '-4') {
        \Drupal::logger('commerce_bitpayir')->error($bitpay_result_second_messages[$result]);
        drupal_set_message($bitpay_result_second_messages[$result], 'error');
      }
      else {
        $bitpay_result_second_messages[-5] = t('Bitpay: unknown error: @error', array('@error' => $result));
      }
    }
  }
}
