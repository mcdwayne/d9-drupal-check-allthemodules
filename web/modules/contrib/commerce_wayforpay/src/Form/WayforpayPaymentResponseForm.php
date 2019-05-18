<?php

namespace Drupal\commerce_wayforpay\Form;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_wayforpay\Helpers\Arr;
use Drupal\commerce_wayforpay\Helpers\Validation;

/**
 * Class WayforpayPaymentResponseForm.
 *
 * @package Drupal\commerce_wayforpay\Form
 */
class WayforpayPaymentResponseForm {
  use WayforpayFormTrait;


  const STATUS_PAYMENT_AUTHORIZED = 'authorization';

  const STATUS_ORDER_COMPLETE = 'completed';

  const STATUS_PAYMENT_FAIL = 'authorization_voided';

  const STATUS_PAYMENT_REFUNDED = 'refunded';

  public $cleanedData = [];

  public $data = [];

  public $isDirty = FALSE;

  public $user;
  public  $payment_gateway;
  /**
   * Order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  public $order;

  public $errors = [];

  /**
   * Constructor.
   *
   * @param array $data
   *   Form data.
   * @param array $config
   *   Payment Gateway config.
   */
  public function __construct(array $data, array $config) {
    $this->data = $data;
    $this->config = $config;
  }

  /**
   * Check form values.
   *
   * @return bool
   *   Result.
   */
  public function isValid() {
    if (empty($this->data)) {
      return FALSE;
    }
    try {
      $this->clean();
    }
    catch (\Exception  $e) {
      $this->errors[] = $e->__toString();
      return FALSE;
    }
    $validation = Validation::factory($this->cleanedData)
      ->rule('merchantAccount', 'not_empty')
      ->rule('orderReference', 'not_empty')
      ->rule('amount', 'not_empty')
      ->rule('currency', 'not_empty')
      ->rule('merchantSignature', 'not_empty');
    $is_valid = $validation->check();
    if ($is_valid === FALSE) {
      $this->errors = $validation->errors;
    }
    return $is_valid;
  }

  /**
   * Perform validation.
   *
   * @return array
   *   Cleaned data.
   *
   * @throws \Exception
   */
  public function clean() {
    /** @var \Drupal\Core\Logger\LoggerChannelInterface $logger */
    $logger                             = \Drupal::logger('commerce_wayforpay');
    $this->cleanedData                  = Arr::extract($this->data, [
      'merchantAccount',
      'merchantDomainName',
      'orderReference',
      'orderDate',
      'amount',
      'currency',
      'productName',
      'productCount',
      'productPrice',
      'merchantSignature',
      'authCode',
      'cardPan',
      'createdDate',
      'transactionStatus',
      'reasonCode',
      'recToken',
      'paymentSystem',
    ]);
    $cleaned_data                       = $this->cleanedData;
    $cleaned_data['merchantDomainName'] = $this->config['merchantDomainName'];
    $order_id                           = $cleaned_data['orderReference'];
    $this->order                        = Order::load($order_id);
    if (!$this->order) {
      $logger->error(
        "WayforpayPaymentResponseForm invalid order {$order_id}  does not exists");
      throw new \Exception("WayforpayPaymentResponseForm invalid order {$order_id}  does not exists");
    }
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order_entity */
    $cleaned_data['productPrice'] = [];
    $cleaned_data['productCount'] = [];
    $cleaned_data['productName'] = [];
    foreach ($this->order->getItems() as $i) {
      $cleaned_data['productPrice'][] = number_format($i->getTotalPrice()
        ->getNumber(), 2, '.', '');
      $cleaned_data['productCount'][] = (int) $i->getQuantity();
      $cleaned_data['productName'][] = $i->getTitle();
    }
    $date_created = $this->order->get('created')->getValue()[0]['value'];
    $cleaned_data['orderDate'] = $date_created;
    $request_signature = $cleaned_data['merchantSignature'];
    $sign_data = [];

    foreach ([
      'merchantAccount',
      'orderReference',
      'amount',
      'currency',
      'authCode',
      'cardPan',
      'transactionStatus',
      'reasonCode',
    ] as $field_name) {
      if (isset($cleaned_data[$field_name])) {
        $sign_data[$field_name] = $cleaned_data[$field_name];
      }
    }
    $origin_signature = $this->makeSignature($sign_data);
    if ($request_signature !== $origin_signature) {
      $logger->error('WayforpayPaymentResponseForm invalid merchantSignature');
      throw new \Exception('WayforpayPaymentResponseForm invalid merchantSignature');
    }
    return $cleaned_data;
  }

  /**
   * Save form.
   */
  public function save() {
    $cleaned_data = $this->cleanedData;
    $transaction_status = $cleaned_data['transactionStatus'];
    /** @var \Drupal\commerce_payment\Entity\Payment $payment_storage */
    $payment_storage = \Drupal::service('entity_type.manager')
      ->getStorage('commerce_payment');
    if ($transaction_status == 'WaitingAuthComplete') {
    }
    elseif ($transaction_status == 'Approved') {
      $payment = $payment_storage->create([
        'state' => self::STATUS_ORDER_COMPLETE,
        'amount' => $this->order->getTotalPrice(),
        'payment_gateway' => $this->payment_gateway,
        'order_id' => $this->order->id(),
        'remote_id' => $cleaned_data['authCode'],
        'remote_state' => $transaction_status,
      ]);
      $payment->save();
    }
    elseif (in_array($transaction_status, ['Refunded', 'Voided'])) {
      $payment = $payment_storage->create([
        'state' => self::STATUS_PAYMENT_REFUNDED,
        'amount' => $this->order->getTotalPrice(),
        'payment_gateway' => $this->payment_gateway,
        'order_id' => $this->order->id(),
        'remote_id' => $cleaned_data['authCode'],
        'remote_state' => $transaction_status,
      ]);
      $payment->save();
    }
    elseif (in_array($transaction_status,
      ['Expired', 'Declined'])) {
      $payment = $payment_storage->create([
        'state' => self::STATUS_PAYMENT_FAIL,
        'amount' => $this->order->getTotalPrice(),
        'payment_gateway' => $this->payment_gateway,
        'order_id' => $this->order->id(),
        'remote_id' => $cleaned_data['authCode'],
        'remote_state' => $transaction_status,
      ]);
      $payment->save();
    }
  }

}
