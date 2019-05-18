<?php

namespace Drupal\commerce_paypal;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentGatewayInterface;
use Drupal\commerce_paypal\Plugin\Commerce\PaymentGateway\CheckoutInterface;
use Drupal\Core\Url;

/**
 * Provides a helper for building the Smart payment buttons.
 */
class SmartPaymentButtonsBuilder implements SmartPaymentButtonsBuilderInterface {

  /**
   * {@inheritdoc}
   */
  public function build(OrderInterface $order, PaymentGatewayInterface $payment_gateway, $commit) {
    $element = [];
    if (!$payment_gateway->getPlugin() instanceof CheckoutInterface) {
      return $element;
    }
    $config = $payment_gateway->getPlugin()->getConfiguration();
    $create_url = Url::fromRoute('commerce_paypal.checkout.create', [
      'commerce_payment_gateway' => $payment_gateway->id(),
      'commerce_order' => $order->id(),
    ]);
    // Note that we're not making use of the payment return route since it
    // cannot be called from the cart page because of the checkout step
    // validation.
    $return_url = Url::fromRoute('commerce_paypal.checkout.approve', [
      'commerce_order' => $order->id(),
    ]);
    $options = [
      'query' => [
        'client-id' => $config['client_id'],
        'intent' => $config['intent'],
        'commit' => $commit ? 'true' : 'false',
        'currency' => $order->getTotalPrice()->getCurrencyCode(),
      ],
    ];
    if (!empty($config['disable_funding'])) {
      $options['query']['disable-funding'] = implode(',', $config['disable_funding']);
    }
    if (!empty($config['disable_card'])) {
      $options['query']['disable-card'] = implode(',', $config['disable_card']);
    }
    $element['#attached']['library'][] = 'commerce_paypal/paypal_checkout';
    $element['#attached']['drupalSettings']['paypalCheckout'] = [
      'src' => Url::fromUri('https://www.paypal.com/sdk/js', $options)->toString(),
      'elementSelector' => '.paypal-buttons-container',
      'onCreateUrl' => $create_url->toString(),
      'onApproveUrl' => $return_url->toString(),
      'flow' => $commit ? 'mark' : 'shortcut',
      'style' => $config['style'],
    ];
    $element += [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#weight' => 100,
      '#attributes' => [
        'class' => ['paypal-buttons-container'],
        'id' => 'paypal-buttons-container',
      ],
    ];
    return $element;
  }

}
