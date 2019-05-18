<?php

namespace Drupal\commerce_payplug_tests\Services;

use Drupal\commerce_payplug\Services\PayPlugServiceInterface;
use Drupal\Core\Url;
use Payplug\Payplug;
use Payplug\Resource\Payment;
use Payplug\Resource\PaymentCard;
use Payplug\Resource\PaymentCustomer;
use Payplug\Resource\PaymentHostedPayment;
use Payplug\Resource\PaymentNotification;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Testing PayPlug encapsulation service class.
 *
 * This class defines some mock methods for testing purpose.
 *
 * @group commerce_payplug
 */
class PayPlugServiceTest implements PayPlugServiceInterface {


  protected $return_url;
  protected $cancel_url;

  /**
   * { @inheritdoc }
   */
  public function createPayPlugPayment(array $data, Payplug $payplug = null) {
    $this->return_url = $data['hosted_payment']['return_url'];
    $payment = (object) [
      'is_live' => FALSE,
      'save_card' => FALSE,
      'is_paid' => TRUE,
      'metadata' => (object) [
        'order_id' => 1,
      ],
      'card' => (object) [
        'brand' => NULL,
        'id' => NULL,
        'metadata' => NULL,
        'exp_year' => 2018,
        'last4' => '4242',
        'exp_month' => 1,
        'country' => 'US'
      ],
      'amount_refunded' => 0,
      'is_refunded' => FALSE,
      'failure' => NULL,
      'created_at' => 1486758204,
      'hosted_payment' => (object) [
        'cancel_url' => 'http://fake.url',
        'payment_url' => 'http://fake.url',
        'paid_at' => 1486758217,
        'return_url' => 'http://fake.url',
      ],
      'is_3ds' => TRUE,
      'customer' => (object) [
        'address2' => null,
        'email' => 'test@test.com',
        'address1' => null,
        'first_name' => 'session',
        'city' => null,
        'last_name' => 'test',
        'postcode' => null,
        'country' => null
      ],
      'id' => 'pay_4ZioFZ88LVGfGFe2AKpXp6',
      'notification' => (object) [
        'url' => 'http://fake.url',
        'response_code' => NULL
      ],
      'object' => 'payment',
      'amount' => 12000,
      'currency' => 'EUR'
    ];
    $payment->hosted_payment->payment_url = Url::fromRoute('commerce_payplug_tests.fake_payplug', [], ['absolute' => TRUE])->toString();
    return $payment;
  }

  /**
   * Fakes a call to Payplug website.
   */
  public function payplug() {

    // Simulate a
    /** @var \GuzzleHttp\Client $client */
    $client = \Drupal::httpClient();
    $response = $client->post(Url::fromRoute('commerce_payment.notify', ['commerce_payment_gateway' => 'offsite_payplug'], ['absolute' => TRUE])->toString(), [
      'json' => [
        'is_live' => FALSE,
        'save_card' => FALSE,
        'is_paid' => TRUE,
        'metadata' => [
          'order_id' => 1,
        ],
        'card' => [
          'brand' => NULL,
          'id' => NULL,
          'metadata' => NULL,
          'exp_year' => 2018,
          'last4' => '4242',
          'exp_month' => 1,
          'country' => 'US'
        ],
        'amount_refunded' => 0,
        'is_refunded' => FALSE,
        'failure' => NULL,
        'created_at' => 1486758204,
        'hosted_payment' => [
          'cancel_url' => 'http://fake.url',
          'payment_url' => 'http://fake.url',
          'paid_at' => 1486758217,
          'return_url' => 'http://fake.url',
        ],
        'is_3ds' => TRUE,
        'customer' => [
          'address2' => null,
          'email' => 'test@test.com',
          'address1' => null,
          'first_name' => 'session',
          'city' => null,
          'last_name' => 'test',
          'postcode' => null,
          'country' => null
        ],
        'id' => 'pay_4ZioFZ88LVGfGFe2AKpXp6',
        'notification' => [
          'url' => 'http://fake.url',
          'response_code' => NULL
        ],
        'object' => 'payment',
        'amount' => 12000,
        'currency' => 'EUR'
      ]
    ]);

    // Redirect to success payment page.
    return new RedirectResponse($this->return_url);
  }

  /**
   * { @inheritdoc }
   */
  public function setApiKey($api_key) {
    // TODO: Implement setApiKey() method.
  }

  /**
   * { @inheritdoc }
   */
  public function treatPayPlugNotification($notification, $authentication = NULL) {
    return Payment::create([
      'is_live' => FALSE,
      'save_card' => FALSE,
      'is_paid' => TRUE,
      'amount_refunded' => 0,
      'is_refunded' => FALSE,
      'failure' => NULL,
      'created_at' => 1486758204,
      'is_3ds' => TRUE,
      'id' => 'pay_4ZioFZ88LVGfGFe2AKpXp6',
      'object' => 'payment',
      'amount' => 12000,
      'currency' => 'EUR',

      'customer' => PaymentCustomer::fromAttributes([
      'address2' => null,
      'email' => 'test@test.com',
      'address1' => null,
      'first_name' => 'session',
      'city' => null,
      'last_name' => 'test',
      'postcode' => null,
      'country' => null
      ]),
      'notification' => PaymentNotification::fromAttributes([
        'url' => 'http://fake.url',
        'response_code' => 200,
      ]),
      'hosted_payment' => PaymentHostedPayment::fromAttributes([
        'cancel_url' => 'http://fake.url',
        'payment_url' => 'http://fake.url',
        'paid_at' => 1486758217,
        'return_url' => 'http://fake.url',
      ]),
      'card' => PaymentCard::fromAttributes([
        'brand' => NULL,
        'id' => NULL,
        'metadata' => NULL,
        'exp_year' => 2018,
        'last4' => '4242',
        'exp_month' => 1,
        'country' => 'US',
      ]),
      'metadata' => [
        'order_id' => 1,
      ],
    ]);
  }
}