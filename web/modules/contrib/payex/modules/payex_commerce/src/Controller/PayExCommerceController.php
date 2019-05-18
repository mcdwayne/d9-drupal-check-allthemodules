<?php

namespace Drupal\payex_commerce\Controller;

use Drupal\commerce\Response\NeedsRedirectException;
use Drupal\commerce_order\Entity\Order;
use Drupal\Core\Controller\ControllerBase;
use Drupal\payex_commerce\Plugin\Commerce\PaymentGateway\PayExInterface;
use Drupal\payex_commerce\Service\PayExCommerceApi;
use Drupal\payex_commerce\Service\PayExCommerceApiFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a route controller for PayEx routes.
 */
class PayExCommerceController extends ControllerBase {

  /**
   * The PayEx Commerce API factory.
   *
   * @var PayExCommerceApiFactory
   */
  protected $apiFactory;

  /**
   * Constructs a PayExCommerceController class.
   */
  public function __construct(PayExCommerceApiFactory $apiFactory) {
    $this->apiFactory = $apiFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('payex_commerce.api_factory')
    );
  }

  /**
   * @param Order $commerce_order
   *   The order that has had it's payment handled.
   *
   * @return array
   *   The render array for the page.
   */
  public function iframeReturn(Order $commerce_order) {
    /** @var PaymentInterface[] $payments */
    $payments = $this->entityTypeManager()->getStorage('commerce_payment')->loadByProperties(['order_id' => $commerce_order->id()]);
    if ($payments) {
      foreach ($payments as $payment) {
        if ($payment->bundle() == 'payex') {
          // Prices are the same, return URL.
          if ($payment->getAmount()->compareTo($commerce_order->getTotalPrice()) === 0) {
            $payex_payment = $payment;
          }
        }
      }
    }

    $fail_render_array = [
      '#cache' => [
        'max-age' => 0,
      ],
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('No information exists about your payment, please wait while the payment window refreshes'),
      '#attached' => [
        'library' => ['payex_commerce/iframe'],
        'drupalSettings' => [
          'payExIframeStatus' => 'fail',
        ],
      ],
    ];

    if (empty($payex_payment)) {
      return $fail_render_array;
    }

    // Check the status of the payment.
    $payment_gateway = $commerce_order->payment_gateway->entity;
    $plugin = $payment_gateway->getPlugin();
    if (!($plugin instanceof PayExInterface)) {
      return $fail_render_array;
    }

    $config = $plugin->getConfiguration();
    /** @var PayExCommerceApi $api */
    $api = $this->apiFactory->get($config['payex_setting_id']);
    $payment = $api->completePayment($payex_payment);
    $count = 0;
    // In case PayEx is not done on their side, try a few times to complete the
    // payment.
    while ((!$payment || $payment->getState()->value == 'new') && $count < 5) {
      $count += 1;
      sleep(1);
      $payment = $api->completePayment($payex_payment);
    }
    if (!$payment || $payment->getState()->value == 'new') {
      return $fail_render_array;
    }

    // Place the order.
    $checkout_flow = $commerce_order->get('checkout_flow')->entity;
    $checkout_flow_plugin = $checkout_flow->getPlugin();
    try {
      $checkout_flow_plugin->redirectToStep('complete');
    } catch (NeedsRedirectException $e) {
      // Don't actually make the redirect.
    }

    return [
      '#cache' => [
        'max-age' => 0,
      ],
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Payment has been processed, please wait while you are being redirected'),
      '#attached' => [
        'library' => ['payex_commerce/iframe'],
        'drupalSettings' => [
          'payExIframeStatus' => 'continue',
        ],
      ],
    ];
  }

}
