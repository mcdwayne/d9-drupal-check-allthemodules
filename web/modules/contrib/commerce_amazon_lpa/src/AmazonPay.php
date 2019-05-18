<?php

namespace Drupal\commerce_amazon_lpa;

use AmazonPay\Client;
use Drupal\commerce_amazon_lpa\Exception\AmazonPayPaymentGatewayFailureException;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Exception\DeclineException;
use Drupal\commerce_payment\Exception\HardDeclineException;
use Drupal\commerce_payment\Exception\InvalidResponseException;
use Drupal\commerce_price\Price;
use Drupal\commerce_price\RounderInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactory;

/**
 * Amazon Pay SDK wrapper.
 */
class AmazonPay {

  /**
   * The Amazon Pay configuration.
   *
   * @var \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   */
  protected $amazonPayConfiguration;

  /**
   * The current merchant account.
   *
   * @var mixed
   */
  protected $currentMerchantAccount;

  /**
   * The API client.
   *
   * @var \AmazonPay\Client
   */
  protected $client;

  /**
   * The rounder.
   *
   * @var \Drupal\commerce_price\RounderInterface
   */
  protected $rounder;

  /**
   * The integration platform IDs.
   *
   * @var array
   */
  protected $platformIds = [
    'UK' => 'A33DP3YE7OHVLV',
    'DE' => 'A1ZBM19RFMXA83',
    'US' => 'A294FY3QW7KJ8X',
  ];

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructor.
   */
  public function __construct(ConfigFactory $config_factory, CurrentMerchantAccountInterface $current_merchant_account, RounderInterface $rounder, TimeInterface $time) {
    $this->amazonPayConfiguration = $config_factory->get('commerce_amazon_lpa.settings');
    $this->currentMerchantAccount = $current_merchant_account->resolve();
    $this->rounder = $rounder;
    $this->time = $time;

    if (!empty($this->currentMerchantAccount['merchant_id'])) {
      $this->client = new Client([
        'merchant_id' => $this->currentMerchantAccount['merchant_id'],
        'access_key' => $this->currentMerchantAccount['mws_access_key'],
        'secret_key' => $this->currentMerchantAccount['mws_secret_key'],
        'client_id' => $this->currentMerchantAccount['lwa_client_id'],
        'region' => $this->currentMerchantAccount['region'],
        'platform_id' => $this->platformIds[$this->currentMerchantAccount['region']],
        'sandbox' => $this->amazonPayConfiguration->get('mode') == 'test',
      ]);
    }
  }

  /**
   * Get the client.
   *
   * @return \AmazonPay\Client
   *   The client.
   */
  public function getClient() {
    return $this->client;
  }

  /**
   * Get the Amazon configuration.
   *
   * @return \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   *   The Amazon config.
   */
  public function getConfiguration() {
    return $this->amazonPayConfiguration;
  }

  /**
   * Gets the current Amazon user info.
   *
   * @param string $access_token
   *   The user's access token.
   *
   * @return mixed
   *   The current Amazon user info.
   */
  public function getUserInfo($access_token) {
    static $user_info;
    if (empty($user_info)) {
      try {
        $user_info = $this->client->getUserInfo($access_token);
      }
      catch (\Exception $e) {
        // No handling.
      }
    }
    return $user_info;
  }

  /**
   * Order reference getter.
   */
  public function getOrderReference(OrderInterface $order) {
    $params = [
      'amazon_order_reference_id' => $order->get('amazon_order_reference')->value,
      'address_consent_token' => (isset($_COOKIE['amazon_Login_accessToken'])) ? $_COOKIE['amazon_Login_accessToken'] : '',
    ];
    $data = [
      'op' => 'getOrderReference',
      'order' => $order,
    ];
    $this->alterRequestParams($params, $data);

    $response = $this->client->getOrderReferenceDetails($params, $data);
    $data = $response->toArray();

    if (!$this->client->success) {
      throw new \Exception($data['Error']['Code'] . ': ' . $data['Error']['Message']);
    }

    return $data['GetOrderReferenceDetailsResult']['OrderReferenceDetails'];
  }

  /**
   * Order reference setter.
   */
  public function setOrderReference(OrderInterface $order) {
    $params = [
      'amazon_order_reference_id' => $order->get('amazon_order_reference')->value,
      'amount' => $this->rounder->round($order->getTotalPrice())->getNumber(),
      'currency_code' => $order->getTotalPrice()->getCurrencyCode(),
      'seller_order_id' => $order->id(),
      'store_name' => $order->getStore()->label(),
    ];
    $data = [
      'op' => 'setOrderReference',
      'order' => $order,
    ];
    $this->alterRequestParams($params, $data);

    $response = $this->client->setOrderReferenceDetails($params);
    $data = $response->toArray();

    if (!$this->client->success) {
      throw new \Exception($data['Error']['Code'] . ': ' . $data['Error']['Message']);
    }

    $reference_details = $data['SetOrderReferenceDetailsResult']['OrderReferenceDetails'];

    if (!empty($reference_details['Constraints'])) {
      throw new \Exception('Order reference has constraints');
    }

    return $data['SetOrderReferenceDetailsResult']['OrderReferenceDetails'];
  }

  /**
   * Confirm order reference.
   */
  public function confirmOrderReference(OrderInterface $order) {
    $params = [
      'amazon_order_reference_id' => $order->get('amazon_order_reference')->value,
    ];
    $data = [
      'op' => 'confirmOrderReference',
      'order' => $order,
    ];
    $this->alterRequestParams($params, $data);

    $response = $this->client->confirmOrderReference($params);
    $data = $response->toArray();

    if (!$this->client->success) {
      throw new \Exception($data['Error']['Code'] . ': ' . $data['Error']['Message']);
    }
  }

  /**
   * Close order reference.
   */
  public function closeOrderReference(OrderInterface $order) {
    $params = [
      'amazon_order_reference_id' => $order->get('amazon_order_reference')->value,
    ];
    $data = [
      'op' => 'closeOrderReference',
      'order' => $order,
    ];
    $this->alterRequestParams($params, $data);

    $response = $this->client->closeOrderReference($params);
    $data = $response->toArray();
    if (!$this->client->success) {
      throw new \Exception($data['Error']['Code'] . ': ' . $data['Error']['Message']);
    }

    return $data;
  }

  /**
   * Authorize payment.
   */
  public function authorize(PaymentInterface $payment) {
    $this->assertPaymentState($payment, ['new']);
    $authorization_mode = $this->amazonPayConfiguration->get('authorization_mode');
    if ($authorization_mode != 'manual') {
      $capture_now = $this->amazonPayConfiguration->get('capture_mode') == 'auth_capture';
      $params = [
        'amazon_order_reference_id' => $payment->getOrder()->get('amazon_order_reference')->value,
        'authorization_amount' => $this->rounder->round($payment->getAmount())->getNumber(),
        'currency_code' => $payment->getAmount()->getCurrencyCode(),
        'authorization_reference_id' => 'auth_' . $payment->id() . '_' . $this->time->getRequestTime(),
        'capture_now' => $capture_now,
        'transaction_timeout' => ($authorization_mode == 'async') ? 1440 : 0,
      ];

      if ($capture_now) {
        $params['seller_authorization_note'] = $this->amazonPayConfiguration->get('auth_statement');
      }

      $data = [
        'op' => 'authorize',
        'payment' => $payment,
      ];
      $this->alterRequestParams($params, $data);

      $response = $this->client->authorize($params);
      $data = $response->toArray();

      if (!$this->client->success) {
        throw new DeclineException($data['Error']['Code'] . ': ' . $data['Error']['Message']);
      }

      $authorization_details = $data['AuthorizeResult']['AuthorizationDetails'];
      if ($authorization_details['AuthorizationStatus']['State'] == 'Declined') {
        switch ($authorization_details['AuthorizationStatus']['ReasonCode']) {
          case 'AmazonRejected':
            throw new AmazonPayPaymentGatewayFailureException(t('Your payment could not be processed. Please try to place the order again using another payment method.'));

          case 'TransactionTimedOut':
            $this->cancel($payment->getOrder());
            throw new AmazonPayPaymentGatewayFailureException(t('Your payment could not be processed. Please try to place the order again using another payment method.'));

          case 'ProcessingFailure':
            $this->cancel($payment->getOrder());
            throw new AmazonPayPaymentGatewayFailureException(t('Your order could not be processed due to a system error. Please try to place the order again.'));

          case 'InvalidPaymentMethod':
          default:
            throw new HardDeclineException(t('Your payment could not be processed, please follow the instructions in the payment method box.'));
        }
      }
      // @todo sync billing address, or throw an event of authorization, which does.

      $payment->setRemoteId($authorization_details['AmazonAuthorizationId']);
      if ($authorization_mode != 'async') {
        $next_state = $capture_now ? 'completed' : 'authorization';
        $payment->setState($next_state);
        if (!$capture_now && !empty($authorization_details['ExpirationTimestamp'])) {
          $expiration = new \DateTime($authorization_details['ExpirationTimestamp']);
          $payment->setExpiresTime($expiration->getTimestamp());
        }
      }
    }
    $payment->save();
  }

  /**
   * Capture payment.
   */
  public function capture(PaymentInterface $payment, Price $amount = NULL) {
    if (!$amount instanceof Price) {
      $amount = $payment->getAmount();
    }
    $params = [
      'amazon_order_reference_id' => $payment->getOrder()->get('amazon_order_reference')->value,
      'amazon_authorization_id' => $payment->getRemoteId(),
      'capture_amount' => $this->rounder->round($amount)->getNumber(),
      'currency_code' => $amount->getCurrencyCode(),
      'capture_reference_id' => 'capture_' . $payment->getOrderId() . '_' . $this->time->getRequestTime(),
      'transaction_timeout' => 0,
    ];
    $data = [
      'op' => 'capture',
      'payment' => $payment,
      'amount' => $amount,
    ];
    $this->alterRequestParams($params, $data);

    $response = $this->client->capture($params);
    $data = $response->toArray();

    if (!$this->client->success) {
      throw new InvalidResponseException($data['Error']['Code']);
    }
    $capture_details = $data['CaptureResult']['CaptureDetails'];
    $payment->setRemoteId($capture_details['AmazonCaptureId']);
    $payment->setState('completed');
    $payment->setAmount($amount);
    $payment->save();
  }

  /**
   * Refund payment.
   */
  public function refund(PaymentInterface $payment, Price $amount = NULL, $notes = '') {
    if (!$amount instanceof Price) {
      $amount = $payment->getAmount();
    }

    $params = [
      'amazon_capture_id' => $payment->getRemoteId(),
      'refund_amount' => $this->rounder->round($amount)->getNumber(),
      'currency_code' => $amount->getCurrencyCode(),
      'refund_reference_id' => 'refund_' . $payment->getOrderId() . '_' . $this->time->getRequestTime(),
      'seller_refund_note' => $notes,
    ];
    $data = [
      'op' => 'refund',
      'payment' => $payment,
    ];
    $this->alterRequestParams($params, $data);

    $response = $this->client->refund($params);
    $data = $response->toArray();

    if (!$this->client->success) {
      throw new InvalidResponseException($data['Error']['Code']);
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
   * Cancel payment.
   */
  public function cancel(OrderInterface $order) {
    $params = [
      'amazon_order_reference_id' => $order->get('amazon_order_reference')->value,
    ];
    $data = [
      'op' => 'cancelOrderReference',
      'order' => $order,
    ];
    $this->alterRequestParams($params, $data);

    $response = $this->client->cancelOrderReference($params);
    $data = $response->toArray();

    if (!$this->client->success) {
      throw new \Exception($data['Error']['Code'] . ': ' . $data['Error']['Message']);
    }
  }

  /**
   * Getting capture details from Amazon.
   */
  public function getCaptureDetails($capture_id) {
    $params = [
      'amazon_capture_id' => $capture_id,
    ];

    // Allow modules to modify the request params.
    $this->alterRequestParams('get_capture_details', $params);

    $response = $this->client->getCaptureDetails($params);
    $data = $response->toArray();

    \Drupal::service('logger.factory')->get('commerce_amazon_lpa')->debug(t('Debugging capture details response: !debug>', [
      '!debug' => '<pre>' . check_plain(print_r($data, TRUE)) . '</pre>',
    ]));

    if ($this->client->success) {
      return $data['GetCaptureDetailsResult']['CaptureDetails'];
    }
    else {
      throw new AmazonPayPaymentGatewayFailureException(t('Unable to get capture details for order @order_id.', [
        '@order_id' => $capture_id,
      ]), $data['Error']['Code']);
    }
  }

  /**
   * Getting authorization details from Amazon.
   */
  public function getAuthorizationDetails($authorization_id) {
    $response = $this->client->getAuthorizationDetails([
      'amazon_authorization_id' => $authorization_id,
    ]);
    $data = $response->toArray();

    \Drupal::service('logger.factory')->get('commerce_amazon_lpa')->debug(t('Debugging authorization details response: !debug>', [
      '!debug' => '<pre>' . check_plain(print_r($data, TRUE)) . '</pre>',
    ]));

    if ($this->client->success) {
      return $data['GetAuthorizationDetailsResult']['AuthorizationDetails'];
    }
    else {
      throw new AmazonPayPaymentGatewayFailureException(t('Unable to get authorization details for order @order_id.', [
        '@order_id' => $authorization_id,
      ]), $data['Error']['Code']);
    }
  }

  /**
   * Getting refund details from Amazon.
   */
  public function getRefundDetails($refund_id) {
    $response = $this->client->getRefundDetails([
      'amazon_refund_id' => $refund_id,
    ]);
    $data = $response->toArray();

    if ($this->client->success) {
      return $data['GetRefundDetailsResult']['RefundDetails'];
    }
    else {
      throw new AmazonPayPaymentGatewayFailureException(t('Unable to get refund details for order @order_id.', [
        '@order_id' => $refund_id,
      ]), $data['Error']['Code']);
    }
  }

  /**
   * Request parameter altering.
   */
  protected function alterRequestParams(&$params, $data) {
    \Drupal::service('module_handler')->alter('commerce_amazon_lpa_request_params', $params, $data);
  }

  /**
   * Asserts that the payment state matches one of the allowed states.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment.
   * @param string[] $states
   *   The allowed states.
   *
   * @throws \InvalidArgumentException
   *   Thrown if the payment state does not match the allowed states.
   */
  protected function assertPaymentState(PaymentInterface $payment, array $states) {
    $state = $payment->getState()->value;
    if (!in_array($state, $states)) {
      throw new \InvalidArgumentException(sprintf('The provided payment is in an invalid state ("%s").', $state));
    }
  }

}
