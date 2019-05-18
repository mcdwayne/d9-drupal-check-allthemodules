<?php

namespace Drupal\commerce_amazon_lpa\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\Query\QueryFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_amazon_lpa\AmazonPay;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_price\Price;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_amazon_lpa\Exception\AmazonPayPaymentGatewayFailureException;
use Drupal\profile\Entity\Profile;
use Drupal\Component\Utility\Html;
use AmazonPay\IpnHandler;

/**
 * IPN controller.
 */
class Ipn extends ControllerBase {

  /**
   * The entity query service.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQueryService;

  /**
   * The Amazon SDK class.
   *
   * @var \Drupal\commerce_amazon_lpa\AmazonPay
   */
  protected $amazonPaySdk;

  /**
   * {@inheritdoc}
   */
  public function __construct(QueryFactory $entity_query_service, AmazonPay $amazon_pay_sdk) {
    $this->entityQueryService = $entity_query_service;
    $this->amazonPaySdk = $amazon_pay_sdk;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query'),
      $container->get('commerce_amazon_lpa.amazon_pay')
    );
  }

  /**
   * Controller method to process an IPN request.
   */
  public function process() {
    $headers = [];
    foreach (\Drupal::request()->server->getIterator() as $name => $value) {
      if (substr($name, 0, 5) == 'HTTP_') {
        $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
      }
    }

    try {
      $ipn_handler = new IpnHandler($headers, file_get_contents('php://input'));
    }
    catch (\Exception $e) {
      return [
        '#markup' => $this->t('Invalid IPN.'),
      ];
    }
    $ipn_message = $ipn_handler->toArray();

    $this->getLogger('commerce_amazon_lpa')->debug(strtr('Amazon IPN debug: !debug', ['!debug' => '<pre>' . print_r($ipn_message, TRUE) . '</pre>']));

    // Decide what to do based on the notification type. Amazon defines several
    // types of notifications. See link below. However, the notification types
    // they define don't appear in the actual responses. e.g.
    // AuthorizationNotification is defined in the docs, but PaymentAuthorize is
    // what actually gets sent with the IPN notification.
    // @see https://payments.amazon.com/documentation/apireference/201757720
    switch ($ipn_message['NotificationType']) {
      case 'OrderReferenceNotification':
        $data = $ipn_message['OrderReference'];
        $order = $this->orderFromAmazonReferenceId($data['AmazonOrderReferenceId']);

        if (empty($order)) {
          $this->getLogger('commerce_amazon_lpa')->error(strtr('Unable to find matching order for reference @id.', ['@id' => $data['AmazonOrderReferenceId']]));
          break;
        }

        $order_data = $order->getData('commerce_amazon_lpa');
        $order_data['order_reference'] = $data;
        $order->setData('commerce_amazon_lpa', $order_data);

        switch ($data['OrderReferenceStatus']['State']) {
          case 'Open':
            if ('sync' != $this->amazonPaySdk->getConfiguration()->get('authorization_mode')) {
              $balance = $this->getOrderBalance($order);

              $zero_price = new Price(0, $balance->getCurrencyCode());
              if ($balance->lessThanOrEqual($zero_price)) {
                break;
              }

              $payment = Payment::create([
                'state' => 'new',
                'amount' => $this->getOrderBalance($order),
                'payment_gateway' => 'amazon_pay',
                'order_id' => $order->id(),
              ]);
              $this->amazonPaySdk->authorize($payment);
              $this->processAuthorizeTransaction($payment, $data);

              $authorization_details = $this->amazonPaySdk->getAuthorizationDetails($data['AmazonAuthorizationId']);
              if (isset($authorization_details['AuthorizationBillingAddress'])) {
                $billing_address = $authorization_details['AuthorizationBillingAddress'];
                try {
                  $this->addressToProfile($order, 'billing', $billing_address);
                  $order->save();
                }
                catch (\Exception $e) {
                  $this->getLogger('commerce_amazon_lpa')->error(strtr('Error processing order billing information for Amazon: !error', ['!error' => '<pre>' . print_r($data, TRUE) . '</pre>']));
                }
              }
            }
            break;

          case 'Canceled':
            $order->cart = 1;
            break;

          case 'Suspended':
            break;

          case 'Closed':
            if ('sync' != $this->amazonPaySdk->getConfiguration()->get('authorization_mode')) {
              $reason_code = $data['OrderReferenceStatus']['ReasonCode'];
              if ($reason_code == 'Expired' || $reason_code == 'AmazonClosed') {
                $order->cart = 1;
              }
              else {
                $order->checkout_step = 'complete';
              }
            }
            break;
        }
        $order->save();
        break;

      case 'PaymentAuthorize':
      case 'AuthorizationNotification':
        $data = $ipn_message['AuthorizationDetails'];

        $payment = $this->getPaymentByRemoteId($data['AmazonAuthorizationId']);
        if ($payment) {
          $data = $this->amazonPaySdk->getAuthorizationDetails($payment->getRemoteId());
          $this->processAuthorizeTransaction($payment, $data);
        }
        else {
          $this->getLogger('commerce_amazon_lpa')->error(strtr('Unable to find matching payment transaction authorization for @id', ['@id' => $data['AmazonAuthorizationId']]));
        }

        break;

      case 'PaymentCapture':
      case 'CaptureNotification':
        $data = $ipn_message['CaptureDetails'];

        // Try to load the transaction first, the ID will have been updated if
        // it was captured / triggered in the UI.
        $payment = $this->getPaymentByRemoteId($data['AmazonCaptureId']);

        // If we have no transaction, we must look up the matching authorization
        // remote ID.
        if (!$payment) {
          $id_components = explode('-', $data['AmazonCaptureId']);
          $id_components[3] = str_replace('C', 'A', $id_components[3]);
          $authorization_id = implode('-', $id_components);
          $payment = $this->getPaymentByRemoteId($authorization_id);
        }

        if ($payment) {
          $this->processCaptureTransaction($payment, $data);
        }
        else {
          $this->getLogger('commerce_amazon_lpa')->error(strtr('Unable to find matching payment transaction capture for @id', ['@id' => $data['AmazonCaptureId']]));
        }
        break;

      case 'PaymentRefund':
      case 'RefundNotification':
        $data = $ipn_message['RefundDetails'];
        $payment = $this->getPaymentByRemoteId($data['AmazonRefundId']);
        if ($payment) {
          $this->processRefundTransaction($payment, $data);
        }
        else {
          $this->getLogger('commerce_amazon_lpa')->error(strtr('Unable to find matching payment transaction refund for @id', ['@id' => $data['AmazonRefundId']]));
        }
        break;

      default:
        $this->getLogger('commerce_amazon_lpa')->debug(strtr('Amazon IPN debug: IPN case did not match for @type.', ['@type' => $ipn_message['NotificationType']]));
        break;
    }
    return [
      '#markup' => $this->t('IPN processed.'),
    ];
  }

  /**
   * Return an entity object based on an Amazon Reference ID.
   *
   * @param string $id
   *   The ID Amazon assigns an order.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface|NULL
   *   The order if found, NULL otherwise.
   */
  protected function orderFromAmazonReferenceId($id) {
    $orders = $this->entityQueryService->get('commerce_order')
      ->condition('amazon_order_reference', $id)
      ->execute();

    if (empty($orders)) {
      return NULL;
    }
    else {
      $order_id = reset($orders);
      return Order::load($order_id);
    }
  }

  /**
   * Get order balance.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order entity.
   *
   * @return \Drupal\commerce_price\Price
   *   The balance Price object.
   */
  protected function getOrderBalance(OrderInterface $order) {
    $payment_ids = $this->entityQueryService->get('commerce_payment')
      ->condition('order_id', $order->id())
      ->condition('state', ['completed', 'partially_refunded'])
      ->execute();
    $balance = new Price(0, $order->getTotalPrice()->getCurrencyCode());
    if ($payment_ids) {
      foreach ($payments as $payment_id) {
        $payment = Payment::load($payment_id);
        $balance->add($payment->getAmount())->subtract($payment->getRefundedAmount());
      }
    }
    return $balance;
  }

  /**
   * Loads a payment based on Amazon Payments remote ID.
   *
   * @param string $remote_id
   *   The remote Amazon Payments reference ID.
   *
   * @return bool|PaymentInterface
   *   False is payment does not exist, other wise the payment.
   */
  protected function getPaymentByRemoteId($remote_id) {
    $payment_ids = $this->entityQueryService->get('commerce_payment')
      ->condition('remote_id', $remote_id)
      ->execute();
    if (!empty($payment_ids)) {
      return Payment::load(reset($payment_ids));
    }
    return FALSE;
  }

  /**
   * Authorization helper.
   */
  public function processAuthorizeTransaction(PaymentInterface $payment, array $data) {
    $order = $payment->getOrder();

    // Amazon has at least two different ways that Buyer information is
    // conveyed back to an API client. The first is that the OrderReference has
    // a Buyer object that contains a name and email address (with an optional
    // phone no.). The second is an AuthorizationBillingDetails object that is
    // returned on some versions of the API. At the time of writing, the US
    // version of the API doesn't provide this information, but the UK version
    // does. Since we do not know what the API is going to return, we'll look
    // for the AuthorizationBillingDetails object and, if it exists, process
    // it. If not, the Buyer information on the Order Reference will have to
    // suffice.
    // @see https://payments.amazon.com/documentation/apireference/201752660#201752450
    // @see https://payments.amazon.co.uk/developer/documentation/apireference/201752450
    if (isset($data['AuthorizationBillingAddress'])) {
      try {
        $this->addressToProfile($order, 'billing', $data['AuthorizationBillingAddress']);
        $order->save();
      }
      catch (\Exception $e) {
        $this->getLogger('commerce_amazon_lpa')->error(strtr('Error processing order billing information for Amazon: !error', ['!error' => '<pre>' . print_r($data, TRUE) . '</pre>']));
      }
    }
    // Otherwise just use shipping address so it isn't empty.
    else {
      $order_reference = $this->getOrderRef($order);
      if (isset($order_reference['Destination']['PhysicalDestination'])) {
        $shipping_address = $order_reference['Destination']['PhysicalDestination'];
        $this->addressToProfile($order, 'billing', $shipping_address);
        $this->addressToProfile($order, 'shipping', $shipping_address);
        $order->save();
      }
    }

    $payment->setRemoteId($data['AmazonAuthorizationId']);
    $payment->setAmount(new Price($data['AuthorizationAmount']['Amount'], $data['AuthorizationAmount']['CurrencyCode']));

    // Capture is only pending if pre-authorized. Otherwise declined during
    // validation. Check the payment object's state and update transaction
    // status.
    $this->paymentStateToStatus($payment, $data['AuthorizationStatus']);

    // If we did capture, set it up so that we can properly refund, etc.
    if ($payment->getState() == 'completed' && $data['AuthorizationStatus']['ReasonCode'] == 'MaxCapturesProcessed') {
      // Create a capture transaction.
      if (isset($data['IdList']['member'])) {
        $capture_id = $data['IdList']['member'];
      }
      else {
        $capture_id = $data['IdList']['Id'];
      }
      $capture_details = $this->amazonPaySdk->getCaptureDetails($capture_id);
      $payment->setRemoteId($capture_details['AmazonCaptureId']);

      // The authorization will be "Closed" but the capture will be
      // "Completed". The value returned by Amazon must be overridden so that
      // refunds will work.
      $this->paymentStateToStatus($payment, $capture_details['CaptureStatus']);
    }
    $payment->save();
  }

  /**
   * Converts an Amazon address into a Commerce Customer profile.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order to attach the profile to. The generated profile will be
   *   attached.
   * @param string $profile_type
   *   The profile type to create. Usually "billing" or "shipping".
   * @param array $amazon_address
   *   The Amazon address data structure included with the AuthorizationDetails.
   *
   * @return \Drupal\profile\Entity\Profile|NULL
   *   The created customer profile.
   */
  protected function addressToProfile(OrderInterface $order, $profile_type, array $amazon_address) {
    if ($profile_type === 'billing') {
      $profile = $order->getBillingProfile();
    }
    elseif ($profile_type === 'shipping') {
      /** @var \Drupal\profile\Entity\ProfileInterface $profile */
      $profile = $order->shipments->first()->entity->getShippingProfile();
    }
    else {
      return;
    }

    if (empty($profile)) {
      $profile = Profile::create([
        'type' => 'customer',
      ]);
      $profile->save();
    }

    $address_state = NULL;
    if (!empty($amazon_address['State'])) {
      $address_state = $amazon_address['State'];
    }
    elseif (!empty($amazon_address['StateOrProvinceCode'])) {
      $address_state = $amazon_address['StateOrProvinceCode'];
    }
    elseif (!empty($amazon_address['StateOrRegion'])) {
      $address_state = $amazon_address['StateOrRegion'];
    }

    $address_thoroughfare = !empty($amazon_address['AddressLine1']) ? $amazon_address['AddressLine1'] : '';
    $address_premise = !empty($amazon_address['AddressLine2']) ? $amazon_address['AddressLine2'] : '';

    $names = explode(' ', $amazon_address['Name']);
    $given_name = array_shift($names);
    $family_name = implode(' ', $names);

    $profile->address->given_name = $given_name;
    $profile->address->family_name = $family_name;
    $profile->address->country = $amazon_address['CountryCode'];
    $profile->address->locality = isset($amazon_address['City']) ? $amazon_address['City'] : '';
    $profile->address->administrative_area = $address_state;
    $profile->address->postal_code = isset($amazon_address['PostalCode']) ? $amazon_address['PostalCode'] : '';
    $profile->address->thoroughfare = $address_thoroughfare;
    $profile->address->premise = $address_premise;
    $profile->save();

    if ($profile_type == 'billing') {
      $order->setBillingProfile($profile);
    }
    else {
      $order->shipments->first()->entity->setShippingProfile($profile);
    }
    return $profile;
  }

  /**
   * Gets the order reference from Amazon Payments.
   *
   * The GetOrderReferenceDetails operation returns details about the Order
   * Reference object and its current state.
   *
   * @link https://payments.amazon.com/documentation/apireference/201751970
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   A commerce order entity.
   *
   * @return array
   *   Array of order reference detail information.
   *
   * @throws \Exception
   */
  protected function getOrderRef(OrderInterface $order) {
    $params = [
      'amazon_order_reference_id' => $order->amazon_order_reference->value,
    ];

    $response = $this->amazonPaySdk->getClient()->getOrderReferenceDetails($params);
    $data = $response->toArray();

    $this->getLogger('commerce_amazon_lpa')->debug(strtr('Debugging get order reference response: !debug', ['!debug' => '<pre>' . Html::escape(print_r($data, TRUE)) . '</pre>']));

    if ($this->amazonPaySdk->getClient()->success) {
      return $data['GetOrderReferenceDetailsResult']['OrderReferenceDetails'];
    }
    else {
      throw new AmazonPayPaymentGatewayFailureException(
        $this->t('Unable to get the order reference for @order_id: @reason', [
          '@order_id' => $order->id(),
          '@reason' => $this->t('@code - @message', [
            '@code' => $data['Error']['Code'],
            '@message' => $data['Error']['Message'],
          ]),
        ]), $data['Error']['Code']);
    }
  }

  /**
   * Sets the status and remote status of a transaction from Amazon state.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment to set the state on.
   * @param array $state
   *   The status that comes from Amazon.
   *
   * @throws \Exception
   *   Exception if there was an invalid state.
   */
  protected function paymentStateToStatus(PaymentInterface $payment, array $state) {
    $payment->setRemoteState($state['State']);

    switch ($state['State']) {
      case 'Completed':
        $payment->setState('completed');
        break;

      case 'Declined':
        $payment->setState('voided');
        break;

      case 'Open':
      case 'Pending':
        $payment->setState('pending');
        break;

      case 'Closed':
        if ($state['ReasonCode'] == 'MaxCapturesProcessed') {
          $payment->setState('completed');
        }
        else {
          $payment->setState('voided');
        }
        break;

      default:
        throw new \Exception($this->t('Unexpected payment object state (@state) returned from Login and Pay with Amazon API', [
          '@state' => print_r($state, TRUE),
        ]));
    }
  }

  /**
   * Processes and saves a payment that is a capture.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The transaction in authorization.
   * @param array $data
   *   API data.
   *
   * @throws \Exception
   *   Exception.
   */
  public function processCaptureTransaction(PaymentInterface $payment, array $data) {
    $payment->setRemoteId($data['AmazonCaptureId']);
    $payment->setAmount(new Price($data['CaptureAmount']['Amount'], $data['CaptureAmount']['CurrencyCode']));
    $this->paymentStateToStatus($payment, $data['CaptureStatus']);
    $payment->save();
  }

  /**
   * Processes and saves a payment that is a refund.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment in refund.
   * @param array $data
   *   API data.
   *
   * @throws \Exception
   *   Exception.
   */
  public function processRefundTransaction(PaymentInterface $payment, array $data) {
    $payment->setRemoteId($data['AmazonRefundId']);
    $payment->setAmount(new Price($data['RefundAmount']['Amount'], $data['RefundAmount']['CurrencyCode']));
    $this->paymentStateToStatus($payment, $data['RefundStatus']);
    $payment->save();
  }

}
