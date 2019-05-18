<?php

namespace Drupal\commerce_iats\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_iats\Exception\GatewayException;
use Drupal\commerce_iats\Exception\GenericPaymentGatewayException;
use Drupal\commerce_payment\CreditCard;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Exception\HardDeclineException;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsAuthorizationsInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsRefundsInterface;
use Drupal\commerce_price\Price;

/**
 * IATS credit card gateway.
 *
 * @CommercePaymentGateway(
 *   id = "commerce_iats_cc",
 *   label = "iATS credit card",
 *   display_label = "iATS credit card",
 *   forms = {
 *     "add-payment-method" = "Drupal\commerce_iats\PluginForm\CreditCardPaymentMethodAddForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex", "discover", "mastercard", "visa",
 *   },
 *   modes = {"live" = "Live"},
 *   js_library = "commerce_iats/cryptogram",
 * )
 */
class CreditCardGateway extends CommerceIatsGatewayBase implements SupportsAuthorizationsInterface, SupportsRefundsInterface {

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
    ];

    try {
      if ($capture) {
        $result = $this->getGateway()->firstPayCcSale(
          $vaultKey,
          $payment_method->getRemoteId(),
          $transaction_data
        );
        $payment->setState('completed');
      }
      else {
        $result = $this->getGateway()->firstPayCcAuth(
          $vaultKey,
          $payment_method->getRemoteId(),
          $transaction_data
        );
        $payment->setState('authorization');
        $payment->setAuthorizedTime(time());
        $payment->setExpiresTime(strtotime('now +30 days'));
      }
    }
    catch (\Exception $e) {
      throw new GenericPaymentGatewayException();
    }

    $payment->setRemoteId($result->referenceNumber);
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function capturePayment(PaymentInterface $payment, Price $amount = NULL) {
    $this->assertPaymentState($payment, ['authorization']);
    // If not specified, capture the entire amount.
    $amount = $amount ?: $payment->getAmount();

    $gateway = $this->getGateway();
    $data = [
      'refNumber' => $payment->getRemoteId(),
      'transactionAmount' => $this->formatAmount($amount->getNumber()),
    ];

    try {
      $gateway->creditCardSettle($data);
    }
    catch (\Exception $e) {
      throw new PaymentGatewayException($this->t('Unable to perform settlement.'));
    }

    $payment->setState('completed');
    $payment->setAmount($amount);
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function voidPayment(PaymentInterface $payment) {
    $this->assertPaymentState($payment, ['authorization']);

    $gateway = $this->getGateway();
    $data = ['refNumber' => $payment->getRemoteId()];

    try {
      $gateway->creditCardVoid($data);
    }
    catch (\Exception $e) {
      throw new PaymentGatewayException($this->t('Unable to perform void.'));
    }

    $payment->setState('authorization_voided');
    $payment->save();
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
    $data = [
      'refNumber' => $payment->getRemoteId(),
      'transactionAmount' => $this->formatAmount($amount->getNumber()),
    ];

    try {
      $gateway->creditCardCredit($data);
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
      $data['creditCardCryptogram'] = $payment_details['cryptogram'];
    }
    else {
      $data['cardNumber'] = $payment_details['number'];
      $data['cardExpMonth'] = $payment_details['expiration']['month'];
      $data['cardExpYear'] = substr($payment_details['expiration']['year'], -2);
      $data['cardType'] = $this->mapCreditCardType($payment_details['type'], TRUE);
    }

    // Add credit card to the vault.
    try {
      $result = $gateway->vaultCcCreate($vaultKey, $data);
      $id = $result->id;
    }
    catch (GatewayException $e) {
      $data = $e->getData()->data;
      if (empty($data->id)) {
        throw new GenericPaymentGatewayException();
      }
      $id = $data->id;
    }
    catch (\Exception $e) {
      throw new GenericPaymentGatewayException();
    }

    // Get the credit card details from the vault.
    try {
      $result = $gateway->vaultCcLoad($vaultKey, $id);
    }
    catch (\Exception $e) {
      throw new GenericPaymentGatewayException();
    }

    $payment_method->card_type = $this->mapCreditCardType($result->cardType);
    $payment_method->card_number = $result->cardNoLast4;
    $payment_method->card_exp_month = $result->cardExpMM;
    $payment_method->card_exp_year = $result->cardExpYY;
    $payment_method->setRemoteId($id);
    $expires = CreditCard::calculateExpirationTimestamp(
      $payment_method->card_exp_month->value,
      $payment_method->card_exp_year->value);
    $payment_method->setExpiresTime($expires);
    $payment_method->save();
  }

  /**
   * {@inheritdoc}
   */
  public function deletePaymentMethod(PaymentMethodInterface $payment_method) {
    $gateway = $this->getGateway();
    $vaultKey = $this->getCommerceIats()
      ->getUserVaultId($payment_method->getOwner());
    $gateway->vaultCcDelete($vaultKey, $payment_method->getRemoteId());
    $payment_method->delete();
  }

  /**
   * Maps iATS credit card types to Commerce credit card types.
   *
   * @param string $cardType
   *   The iATS credit card type.
   * @param bool $rev
   *   Reverse the lookup, to get iATS credit card type from Commerce credit
   *   card type.
   *
   * @return string
   *   The Commerce credit card type.
   */
  public function mapCreditCardType($cardType, $rev = FALSE) {
    $map = [
      'Amex' => 'amex',
      'Discover' => 'discover',
      'MasterCard' => 'mastercard',
      'Visa' => 'visa',
    ];

    if ($rev) {
      $map = array_flip($map);
    }

    if (!isset($map[$cardType])) {
      throw new HardDeclineException(sprintf('Unsupported credit card type "%s".', $cardType));
    }

    return $map[$cardType];
  }

}
