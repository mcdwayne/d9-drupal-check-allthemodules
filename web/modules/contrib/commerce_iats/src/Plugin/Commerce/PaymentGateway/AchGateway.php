<?php

namespace Drupal\commerce_iats\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_iats\Exception\GenericPaymentGatewayException;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsRefundsInterface;
use Drupal\commerce_price\Price;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AchGateway.
 *
 * @CommercePaymentGateway(
 *   id = "commerce_iats_ach",
 *   label = "iATS ACH",
 *   display_label = "iATS ACH",
 *   forms = {
 *     "add-payment-method" = "Drupal\commerce_iats\PluginForm\AchPaymentMethodAddForm",
 *   },
 *   payment_method_types = {"commerce_iats_ach"},
 *   modes = {"live" = "Live"},
 *   js_library = "commerce_iats/cryptogram",
 * )
 */
class AchGateway extends CommerceIatsGatewayBase implements SupportsRefundsInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['ach_category' => ''] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['ach_category'] = [
      '#type' => 'textfield',
      '#title' => t('ACH category'),
      '#description' => t('Enter an ACH category as configured in your transaction center.'),
      '#default_value' => $this->configuration['ach_category'],
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
      $this->configuration['ach_category'] = $values['ach_category'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createPayment(PaymentInterface $payment, $capture = TRUE) {
    $this->assertPaymentState($payment, ['new']);
    $payment_method = $payment->getPaymentMethod();
    $this->assertPaymentMethod($payment_method);
    $amount = $payment->getAmount();

    $account = $payment->getOrder()->getCustomer();
    $vaultKey = $this->getCommerceIats()->getUserVaultId($account);

    $transaction_data = [
      'orderId' => $payment->getOrderId() . '-' . $this->time->getCurrentTime(),
      'transactionAmount' => $this->formatAmount($amount->getNumber()),
      'categoryText' => $this->configuration['ach_category'],
    ];

    try {
      $result = $this->getGateway()->firstPayAchDebit(
        $vaultKey,
        $payment_method->getRemoteId(),
        $transaction_data
      );
      $payment->setState('completed');

      $payment->setRemoteId($result->referenceNumber);
      $payment->save();
    }
    catch (\Exception $e) {
      throw new GenericPaymentGatewayException();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function refundPayment(PaymentInterface $payment, Price $amount = NULL) {
    $this->assertPaymentState($payment, ['completed', 'partially_refunded']);
    // If not specified, refund the entire amount.
    $amount = $amount ?: $payment->getAmount();
    $this->assertRefundAmount($payment, $amount);

    $gateway = $this->getGateway();
    $account = $payment->getOrder()->getCustomer();
    $vaultKey = $this->getCommerceIats()->getUserVaultId($account);
    $payment_method = $payment->getPaymentMethod();

    $data = [
      'refNumber' => $payment->getRemoteId(),
      'transactionAmount' => $this->formatAmount($amount->getNumber()),
      'categoryText' => $this->configuration['ach_category'],
      'orderId' => $payment->getOrderId() . '-' . $this->time->getCurrentTime(),
    ];

    try {
      $gateway->firstPayAchDebit($vaultKey, $payment_method->getRemoteId(), $data);
    }
    catch (\Exception $e) {
      throw new PaymentGatewayException($this->t('Unable to perform refund.'));
    }

    $old_refunded_amount = $payment->getRefundedAmount();
    $new_refunded_amount = $old_refunded_amount->add($amount);
    if ($new_refunded_amount->lessThan($payment->getAmount())) {
      $payment->setState('partially_refunded');
    }
    else {
      $payment->setState('refunded');
    }

    $payment->setRefundedAmount($new_refunded_amount);
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function createPaymentMethod(PaymentMethodInterface $payment_method, array $payment_details) {
    $gateway = $this->getGateway();
    $vaultKey = $this->getCommerceIats()
      ->getUserVaultId($payment_method->getOwner());

    $data = $this->setPaymentMethodBillingInfo($payment_method);

    if ($this->isHosted()) {
      $data['achCryptogram'] = $payment_details['cryptogram'];
    }
    else {
      $data['aba'] = $payment_details['routing_number'];
      $data['dda'] = $payment_details['account_number'];
      $data['accountType'] = $payment_details['account_type'];
    }

    // Add bank account to the vault.
    try {
      $result = $gateway->vaultAchCreate($vaultKey, $data);
      $id = $result->id;
    }
    catch (\Exception $e) {
      throw new GenericPaymentGatewayException();
    }

    // Get the bank account details from the vault.
    try {
      $result = $gateway->vaultAchLoad($vaultKey, $id);
    }
    catch (\Exception $e) {
      throw new GenericPaymentGatewayException();
    }

    $payment_method->account_number = $result->ddaLast4;
    $payment_method->account_type = $result->accountType;
    $payment_method->setRemoteId($id);
    $payment_method->save();
  }

  /**
   * {@inheritdoc}
   */
  public function deletePaymentMethod(PaymentMethodInterface $payment_method) {
    $gateway = $this->getGateway();
    $vaultKey = $this->getCommerceIats()
      ->getUserVaultId($payment_method->getOwner());
    $gateway->vaultAchDelete($vaultKey, $payment_method->getRemoteId());
    $payment_method->delete();
  }

}
