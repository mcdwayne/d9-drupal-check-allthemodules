<?php

namespace Drupal\commerce_adyen\Adyen;

use Adyen\Service\Modification as ModificationBase;

/**
 * Abstract modification request.
 */
abstract class Modification {

  use Client;

  /**
   * Marker of request to refund the money.
   */
  const REFUND = 'refund';
  /**
   * Marker of request to capture the money.
   */
  const CAPTURE = 'capture';

  /**
   * The type of modification request to send.
   *
   * @var string
   */
  private $modificationType = '';
  /**
   * Transaction.
   *
   * @var \Drupal\commerce_adyen\Adyen\Transaction\Payment
   *   Payment|Refund
   */
  protected $transaction;

  /**
   * Modification constructor.
   *
   * @param \stdClass|int|string $order
   *   Commerce order object or order ID.
   * @param string $remote_transaction_status
   *   Remote status of the transaction.
   * @param string $modification_type
   *   The type of modification request to send. Use one of constants.
   */
  public function __construct($order, $remote_transaction_status, $modification_type) {
    $this->modificationType = $modification_type;
    $this->transaction = commerce_adyen_get_transaction_instance($this->getTransactionType(), $order, $remote_transaction_status);
  }

  /**
   * Checks whether modification is available.
   *
   * @return bool
   *   A state of check.
   */
  abstract public function isAvailable();

  /**
   * Send a modification request.
   *
   * @return bool
   *   Whether request was successfully sent or not.
   *
   * @throws \Adyen\AdyenException
   * @throws \InvalidArgumentException
   *
   * @link https://github.com/Adyen/adyen-php-sample-code/blob/master/4.Modifications/httppost/refund.php
   * @link https://github.com/Adyen/adyen-php-sample-code/blob/master/4.Modifications/httppost/capture.php
   */
  final public function request() {
    $payment_method = $this->transaction->getPaymentMethod();
    $currency_code = $this->transaction->getCurrency();
    $order = $this->transaction->getOrder()->value();

    // Make an API call to tell Adyen that we are waiting for notification
    // from it.
    $request = [
      'reference' => $order->order_number,
      'merchantAccount' => $payment_method['settings']['merchant_account'],
      'originalReference' => $this->transaction->getRemoteId(),
      'modificationAmount' => [
        'currency' => $currency_code,
        // Adyen doesn't accept amount with preceding minus.
        'value' => abs(commerce_adyen_amount($this->transaction->getAmount(), $currency_code)),
      ],
    ];

    $modification = (new ModificationBase($this->getClient($payment_method)))->{$this->modificationType}($request);
    $status = "[{$this->modificationType}-received]" === $modification['response'];
    $hook = $status ? 'received' : 'rejected';

    watchdog(COMMERCE_ADYEN_PAYMENT_METHOD, "Request for %modification_type has been {$hook} by Adyen: <pre>@payload</pre>", [
      '@payload' => var_export($request, TRUE),
      '%modification_type' => $this->modificationType,
    ]);

    module_invoke_all("commerce_adyen_{$this->modificationType}_{$hook}", $this->transaction, $order);

    return $status;
  }

  /**
   * Validate modification type and return transaction type.
   *
   * @return string
   *   Type of transaction.
   */
  private function getTransactionType() {
    switch ($this->modificationType) {
      case self::REFUND:
        return 'refund';

      case self::CAPTURE:
        return 'payment';

      default:
        throw new \InvalidArgumentException(t('The "@modification" modification request is not supported.', [
          '@modification' => $this->modificationType,
        ]));
    }
  }

}
