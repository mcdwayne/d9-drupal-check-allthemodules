<?php

namespace Drupal\ms_commerce_saman\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Url;

/**
 * Provides the Off-site Redirect payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "ms_commerce_saman_redirect",
 *   label = " Saman (Off-site redirect)",
 *   display_label = "Saman",
 *    forms = {
 *     "offsite-payment" =
 *   "Drupal\ms_commerce_saman\PluginForm\OffsiteRedirect\PaymentOffsiteForm",
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
      '#description' => t('The merchat code which is provided by Saman bank.'),
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
    $res_num = $request->request->get('ResNum');
	$ref_num = $request->request->get('RefNum');
	$transaction_state = $request->request->get('State');
    $merchant_code = $request->request->get('MID');

    // Prevents double spending:
    // If a bad manner user have a successfull transaction and want
    // to have another payment with previous trans_id, we must prevent him/her.
    $query = \Drupal::entityQuery('commerce_payment')
      ->condition('remote_state', $ref_num);
    $payments = $query->execute();
    if (count($payments) > 0) {
      \Drupal::logger('ms_commerce_saman')
        ->error('Saman: Double spending occured on order <a href="@url">%order</a> from ip @ip', [
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

    $url = 'https://sep.shaparak.ir/payments/referencepayment.asmx?WSDL';
   
    if ($transaction_state == 'OK') {
      $client = new \SoapClient($url);

	  $result = $client->VerifyTransaction($ref_num, $merchant_code);

      if ($result > 0) {
        $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
        $payment = $payment_storage->create([
          'state' => 'completed',
          'amount' => $order->getTotalPrice(),
          'payment_gateway' => $this->entityId,
          'order_id' => $order->id(),
          'test' => false,
          'remote_id' => $res_num,
          'remote_state' => $RefNum,
          'authorized' => $this->time->getRequestTime(),
        ]);
        $payment->save();
        drupal_set_message($this->t('Payment was processed'));
      }
      else {
        drupal_set_message($this->t('Transaction failed. Status:') . $result);
      }
    }
    else {
      drupal_set_message($this->t('Transaction canceled by user'));
    }
  }
}
