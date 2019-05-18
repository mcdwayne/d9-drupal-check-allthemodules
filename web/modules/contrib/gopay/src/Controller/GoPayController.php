<?php

namespace Drupal\gopay\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\gopay\GoPayApiInterface;
use Drupal\gopay\GoPayFactoryInterface;
use Drupal\gopay\GoPayState;
use GoPay\Definition\Payment\PaymentInstrument;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GoPay\Definition\Payment\Currency;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Url;

/**
 * Class GopPayController.
 */
class GoPayController extends ControllerBase {

  /**
   * GoPay Api.
   *
   * @var \Drupal\gopay\GoPayApiInterface
   */
  protected $goPayApi;

  /**
   * GoPay Factory.
   *
   * @var \Drupal\gopay\GoPayFactoryInterface
   */
  protected $goPayFactory;

  /**
   * GopPayController constructor.
   *
   * @param \Drupal\gopay\GoPayApiInterface $go_pay_api
   *   GoPayApi Service.
   * @param \Drupal\gopay\GoPayFactoryInterface $go_pay_factory
   *   GoPayFactory Service.
   */
  public function __construct(GoPayApiInterface $go_pay_api, GoPayFactoryInterface $go_pay_factory) {
    $this->goPayApi = $go_pay_api;
    $this->goPayFactory = $go_pay_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('gopay.api'),
      $container->get('gopay.factory')
    );
  }

  /**
   * Default return callback page for GoPay.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request.
   *
   * @return array
   *   Render array.
   */
  public function returnCallback(Request $request) {
    $response = $this->goPayApi->getPaymentStatus($request->get('id'));
    return [];
  }

  /**
   * Default notification callback page for GoPay.
   */
  public function notificationCallback() {
    return [
      '#markup' => 'Notification callback',
    ];
  }

  /**
   * Test callback page for GoPay.
   */
  public function testCallback() {
    $pay_it = $this->goPayFactory->createStandardPayment()
      ->setCurrency(Currency::EUROS)
      ->setDefaultPaymentInstrument(PaymentInstrument::PAYMENT_CARD)
      ->setAllowedPaymentInstruments([PaymentInstrument::PAYMENT_CARD, PaymentInstrument::BANK_ACCOUNT])
      ->setContact($this->goPayFactory->createContact()
        ->setFirstName('John')
        ->setLastName('Smith')
        ->setEmail('jsmith@gmail.com')
        ->setCity('City')
        ->setPhoneNumber(123456)
        ->setStreet('Main 1')
        ->setPostalCode(13)
        ->setCountryCode('USA')
      )
      ->addItem($this->goPayFactory->createItem()
        ->setName('laptop')
        ->setAmount(900, FALSE)
      )
      ->addItem($this->goPayFactory->createItem()
        ->setName('beer')
        ->setAmount(150)
      )
      ->setAmount(20150)
      ->setOrderNumber(123)
      ->setOrderDescription('description')
      ->setReturnUrl(Url::fromRoute(
        'gopay.test.result',
        [],
        [
          'absolute' => TRUE,
        ]
      )->toString()
      );

    return $pay_it->buildInlineForm();
  }

  /**
   * Test callback page to return from GoPay.
   */
  public function testCallbackResult(Request $request) {
    // GoPay return payment id in URL.
    $payment_id = $request->get('id');

    // Load payment status from GoPay.
    $payment_response = $this->goPayFactory->createResponseStatus($payment_id);

    if ($payment_response->isPaid()) {
      // Payment ok.
      drupal_set_message('Payment paid.');
    }
    else {
      // Payment error or canceled.
      $state = $payment_response->getState();
      drupal_set_message('Payment error: ' . GoPayState::getStateDescription($state), 'error');
    }

    return [
      '#markup' => 'Test payment callback result.',
    ];
  }

}
