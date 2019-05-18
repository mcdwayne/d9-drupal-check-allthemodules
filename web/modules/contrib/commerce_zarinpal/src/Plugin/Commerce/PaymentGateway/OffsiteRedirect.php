<?php

namespace Drupal\commerce_zarinpal\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Url;

/**
 * Provides the Off-site Redirect payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "commerce_zarinpal_redirect",
 *   label = " Zarinpal (Off-site redirect)",
 *   display_label = "Zarinpal",
 *    forms = {
 *     "offsite-payment" =
 *   "Drupal\commerce_zarinpal\PluginForm\OffsiteRedirect\PaymentOffsiteForm",
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
        'merchant_code' => 'Enter your Merchant Code',
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['merchant_code'] = [
      '#type' => 'textfield',
      '#title' => t('Merchant Code'),
      '#default_value' => $this->configuration['merchant_code'],
      '#description' => t('The merchat code which is provided by Zarinpal. If you use the gateway in the Test mode, You can use an arbitrary code, for example: 123'),
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
      $this->configuration['merchant_code'] = $values['merchant_code'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    $authority = $request->query->get('Authority');
    $merchant_code = $this->configuration['merchant_code'];

    // Prevents double spending:
    // If a bad manner user have a successfull transaction and want
    // to have another payment with previous trans_id, we must prevent him/her.
    $query = \Drupal::entityQuery('commerce_payment')
      ->condition('remote_state', $authority);
    $payments = $query->execute();
    if (count($payments) > 0) {
      \Drupal::logger('commerce_zarinpal')
        ->error('Zarinpal: Double spending occured on order <a href="@url">%order</a> from ip @ip', [
          '@url' => Url::fromUri('base:/admin/commerce/orders/' . $order->id())
            ->toString(),
          '%order' => $order->id(),
          '@ip' => $order->getIpAddress(),
        ]);
      drupal_set_message('Double spending occured.', 'error');
      /** @var \Drupal\commerce_checkout\Entity\CheckoutFlowInterface $checkout_flow */
      $checkout_flow = $order->checkout_flow->entity;
      $checkout_flow_plugin = $checkout_flow->getPlugin();
      $redirect_step = $checkout_flow_plugin->getPreviousStepId('payment');
      $checkout_flow_plugin->redirectToStep($redirect_step);
    }

    // Checks if we are in debug mode
    if ($this->configuration['mode'] == 'test') {
      $url = 'https://sandbox.zarinpal.com/pg/services/WebGate/wsdl';
    }
    elseif ($this->configuration['mode'] == 'live') {
      $url = 'https://zarinpal.com/pg/services/WebGate/wsdl';
    }

    if ($request->query->get('Status') == 'OK') {
      $client = new \SoapClient($url, ['encoding' => 'UTF-8']);
      $amount = (int) $order->getTotalPrice()->getNumber();
      if ($order->getTotalPrice()->getCurrencyCode() == 'IRR') {
        // Converts Iranian Rials to Toman
        $amount = $amount / 10;
      }
      $result = $client->PaymentVerification(
        [
          'MerchantID' => $merchant_code,
          'Authority' => $authority,
          'Amount' => $amount,
        ]
      );
      if ($result->Status == 100) {
        $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
        $payment = $payment_storage->create([
          'state' => 'completed',
          'amount' => $order->getTotalPrice(),
          'payment_gateway' => $this->entityId,
          'order_id' => $order->id(),
          'test' => $this->getMode() == 'test',
          'remote_id' => $result->RefID,
          'remote_state' => $authority,
          'authorized' => $this->time->getRequestTime(),
        ]);
        $payment->save();
        drupal_set_message($this->t('Payment was processed'));
      }
      else {
        drupal_set_message($this->t('Transaction failed. Status:') . $result->Status);
      }
    }
    else {
      drupal_set_message($this->t('Transaction canceled by user'));
    }
  }
}
