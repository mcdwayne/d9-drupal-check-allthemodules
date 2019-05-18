<?php

namespace Drupal\commerce_adyen\Adyen\Authorisation;

/**
 * Payment authorisation response.
 *
 * @link https://docs.adyen.com/developers/hpp-manual#hpppaymentresponse
 */
class Response extends Signature {

  /**
   * An error occurred during the payment processing.
   */
  const ERROR = 'ERROR';
  /**
   * Payment in pending.
   *
   * It is not possible to obtain the final status of the payment. This
   * can happen if the systems providing final status information for the
   * payment are unavailable, or if the shopper needs to take further action
   * to complete the payment.
   */
  const PENDING = 'PENDING';
  /**
   * Payment authorisation was unsuccessful.
   */
  const REFUSED = 'REFUSED';
  /**
   * Payment cancelled.
   *
   * Payment was cancelled by the shopper before completion, or the shopper
   * returned to the merchant's site before completing the transaction.
   */
  const CANCELLED = 'CANCELLED';
  /**
   * Payment authorisation was successfully completed.
   */
  const AUTHORISED = 'AUTHORISED';

  /**
   * Payment transaction.
   *
   * @var \Drupal\commerce_adyen\Adyen\Transaction\Payment
   */
  private $transaction;

  /**
   * Response constructor.
   *
   * @param \stdClass $order
   *   Commerce order.
   * @param array $payment_method
   *   Payment method information.
   *
   * @throws \Adyen\AdyenException
   */
  public function __construct(\stdClass $order, array $payment_method) {
    $query = [];

    // Using $_GET all dots will be converted to underscores.
    // @see http://stackoverflow.com/a/68742
    foreach (explode('&', $_SERVER['QUERY_STRING']) as $pair) {
      list($name, $value) = explode('=', $pair);

      $query[urldecode($name)] = urldecode($value);
    }

    // Query will contain:
    // @code
    // [
    //   'authResult' => 'AUTHORISED',
    //   'merchantReference' => 'DE-LW-7880',
    //   'merchantReturnData' => 'ErO8-G_RWHQoXnsYE2RFDQnidI7guyYvlKNoHAqLXVg',
    //   'merchantSig' => '3F2/9VUurEo6gYiDK52Tgiq6chfnDtw3ffDSx9VuWNM=',
    //   'paymentMethod' => 'visa',
    //   'pspReference' => '8524957053594505',
    //   'shopperLocale' => 'de',
    //   'skinCode' => 'iL6lEu2Q',
    // ]
    // @endcode
    $this->data = drupal_get_query_parameters($query);

    if (empty($this->data)) {
      throw new \UnexpectedValueException(t('Empty response from Adyen has been received.'));
    }

    parent::__construct($order, $payment_method);

    // The "getSignature" method uses "$this->data" property for generating
    // signature. As this is response from Adyen we should not perform any
    // modifications of the values in this property until signature will not
    // be generated. In simple words: we should use raw data for calculation.
    if ($this->getSignature() !== $this->data['merchantSig']) {
      throw new \UnexpectedValueException(t('Received Adyen response with invalid signature.'));
    }

    $this->transaction = commerce_adyen_get_transaction_instance('payment', $order);
    $this->transaction->setPayload($this->data);
    $this->transaction->setRemoteStatus($this->data['authResult']);

    // The "pspReference" property will not exist in cases like
    // payment cancellation or similar.
    if (isset($this->data['pspReference'])) {
      $this->transaction->setRemoteId($this->data['pspReference']);
    }
  }

  /**
   * Returns data received in response from Adyen.
   *
   * @return \stdClass
   *   Received data.
   */
  public function getReceivedData() {
    return (object) $this->data;
  }

  /**
   * Returns authentication result.
   *
   * @return string
   *   Value of one of constants of this class.
   */
  public function getAuthenticationResult() {
    return $this->data['authResult'];
  }

  /**
   * Returns payment transaction.
   *
   * @return \Drupal\commerce_adyen\Adyen\Transaction\Payment
   *   Payment transaction.
   */
  public function getTransaction() {
    return $this->transaction;
  }

}
