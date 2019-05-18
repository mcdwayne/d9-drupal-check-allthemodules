<?php

namespace Drupal\commerce_pasargad\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;
use Drupal\commerce_pasargad\Pasargad;


/**
 * Provides the Off-site Redirect payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "commerce_pasargad_redirect",
 *   label = " Pasargad (Off-site redirect)",
 *   display_label = "Pasargad",
 *    forms = {
 *     "offsite-payment" =
 *   "Drupal\commerce_pasargad\PluginForm\OffsiteRedirect\PaymentOffsiteForm",
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
      'terminal_code' => 'Enter your Terminal Code',
      'private_key' => 'Enter your Private key',
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
      '#description' => t('The merchant code which is provided by Pasargad If you use the gateway in the Test mode, You can use an arbitrary code, for example: 123'),
      '#required' => TRUE,
    ];
    $form['terminal_code'] = [
      '#type' => 'textfield',
      '#title' => t('Terminal Code'),
      '#default_value' => $this->configuration['terminal_code'],
      '#description' => t('The terminal code which is provided by Pasargad.'),
      '#required' => TRUE,
    ];
    $form['private_key'] = [
      '#type' => 'textarea',
      '#title' => t('Private Key'),
      '#default_value' => $this->configuration['private_key'],
      '#description' => t('Obtain private key in the certificate.xml and copy it here.'),
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
      $this->configuration['terminal_code'] = $values['terminal_code'];
      $this->configuration['private_key'] = $values['private_key'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    $tref = $request->query->get('tref');

    // Prevents double spending:
    // If a bad manner user have a successful transaction and want
    // to have another payment with previous tref, we must prevent them.
    $query = \Drupal::entityQuery('commerce_payment')
      ->condition('remote_id', $tref);
    $payments = $query->execute();
    if (count($payments) > 0) {
      \Drupal::logger('commerce_pasargad')
        ->error('commerce_pasargad: Double spending occured on order <a href="@url">%order</a> from ip @ip', [
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
    else {
      try {
        $check_transaction_url = 'https://pep.shaparak.ir/CheckTransactionResult.aspx';
        $transaction_data = 'invoiceUID=' . $tref;
        $xml_check_transaction = Pasargad::post($check_transaction_url, $transaction_data);

        $resultObj = new \SimpleXMLElement($xml_check_transaction);
        if ($resultObj->result->__toString() === 'True') {
          $merchant_code = $this->configuration['merchant_code'];
          $terminal_code = $this->configuration['terminal_code'];
          $private_key = $this->configuration['private_key'];
          $amount = (int) $order->getTotalPrice()->getNumber();
          if ($order->getTotalPrice()->getCurrencyCode() != 'IRR') {
            // Treats all of the currency codes other than the 'IRR', as Iranian Toman
            // and converts them to Iranian Rials by multiplying by 10.
            $amount = $amount * 10;
          }
          $timestamp = time();
          $order_id = $order->id();
          $sign = Pasargad::sign([
            $merchant_code,
            $terminal_code,
            $order_id,
            $order->getCreatedTime(),
            $amount,
            $timestamp,
          ],
            $private_key);

          $verification_data = "MerchantCode=" . $merchant_code . "&TerminalCode=" . $terminal_code . "&InvoiceNumber=" . $order_id . "&InvoiceDate=" . $order->getCreatedTime() . "&amount=" . $amount . "&TimeStamp=" . $timestamp . "&sign=" . $sign;
          $verification_url = 'https://pep.shaparak.ir/VerifyPayment.aspx';
          $xml_result_verification = Pasargad::post($verification_url, $verification_data);

          $resultVerificationObj = new \SimpleXMLElement($xml_result_verification);

          if ($resultVerificationObj->result->__toString() === 'True') {
            $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
            $payment = $payment_storage->create([
              'state' => 'completed',
              'amount' => $order->getTotalPrice(),
              'payment_gateway' => $this->entityId,
              'order_id' => $order->id(),
              'remote_id' => $tref,
              'authorized' => $this->time->getRequestTime(),
            ]);
            $payment->save();
            drupal_set_message($this->t('Pasargad gateway: ' . $resultVerificationObj->resultMessage->__toString()));
          }
          else {
            drupal_set_message($this->t('Pasargad gatway: ' . $resultVerificationObj->resultMessage->__toString()), 'error');
          }

        }
        else {
          drupal_set_message($this->t("Transaction failed.", 'error'));
        }

      } catch (\Exception $e) {
        drupal_set_message('Error: ' . $e->getMessage(), 'error');
      }
    }
  }
}
