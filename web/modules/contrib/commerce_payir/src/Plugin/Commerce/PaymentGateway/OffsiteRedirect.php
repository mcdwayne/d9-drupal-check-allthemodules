<?php

namespace Drupal\commerce_payir\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Url;

/**
 * Provides the Off-site Redirect payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "commerce_payir_redirect",
 *   label = " Pay.ir (Off-site redirect)",
 *   display_label = "Pay.ir",
 *    forms = {
 *     "offsite-payment" =
 *   "Drupal\commerce_payir\PluginForm\OffsiteRedirect\PaymentOffsiteForm",
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
        'api_key' => 'test',
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => t('API Key'),
      '#default_value' => $this->configuration['api_key'],
      '#description' => t("The API Key which is provided by Pay.ir. If you use the gateway in the Test mode, just enter the 'test' word."),
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
      $this->configuration['api_key'] = $values['api_key'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    // The parameters which is posted to the callback url by Pay.ir
    $status = $request->request->get('status');
    $transId = $request->request->get('transId');
    $message = $request->request->get('message');

    $api_key = $this->configuration['api_key'];

    // Prevents double spending:
    // If a bad manner user have a successful transaction and want
    // to have another payment with previous transId, we must prevent them.
    $query = \Drupal::entityQuery('commerce_payment')
      ->condition('remote_state', $transId);
    $payments = $query->execute();
    if (count($payments) > 0) {
      \Drupal::logger('commerce_payir')
        ->error('commerce_payir: Double spending occured on order <a href="@url">%order</a> from ip @ip', [
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

    $verify_url = 'https://pay.ir/payment/verify';
    // Status of the transaction which is posted to the callback url by Pay.ir
    if ($status == 1) {
      try {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $verify_url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "api=$api_key&transId=$transId");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $res = curl_exec($ch);
        curl_close($ch);
        // Verification result
        $result = json_decode($res);
        // Status of the transaction verification
        if ($result->status == 1) {
          $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
          $payment = $payment_storage->create([
            'state' => 'completed',
            'amount' => $order->getTotalPrice(),
            'payment_gateway' => $this->entityId,
            'order_id' => $order->id(),
            'test' => $this->getMode() == 'test',
            'remote_id' => $transId,
            'authorized' => $this->time->getRequestTime(),
          ]);
          $payment->save();
          drupal_set_message($this->t('Payment was processed.'));
        }
        else {
          drupal_set_message($this->t('Verification failed.')
            . ' Pay.ir: status: ' . $result->status
            . ' & errorCode: ' . $result->errorCode
            . ' & errorMessage: ' . $result->errorMessage);
        }
      } catch (\Exception $e) {
        drupal_set_message('Error: ' . $e->getMessage(), 'error');
      }

    }
    else {
      drupal_set_message($this->t('Pay.ir: ' . $message, 'error'));
    }
  }
}
