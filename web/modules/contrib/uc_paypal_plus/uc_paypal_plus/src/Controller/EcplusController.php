<?php

namespace Drupal\uc_paypal_plus\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\uc_order\Entity\Order;
use Drupal\uc_order\OrderInterface;
use Drupal\Core\Url;
/**
 * Returns responses for PayPal routes.
 */
class EcplusController extends ControllerBase {

  /**
   * Completes the transaction for PPP Express Checkout Mark Flow.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect to the order complete page (on success) or cart (on failure).
   */
  public function ecComplete() {
    $session = \Drupal::service('session');

    if (!$session->has('TOKEN') || !($order = Order::load($session->get('cart_order')))) {
      $session->remove('cart_order');
      $session->remove('TOKEN');
      $session->remove('PAYERID');
      drupal_set_message($this->t('An error has occurred in your PayPal payment. Please review your cart and try again.'));
      return $this->redirect('uc_cart.cart');
    }

    
    // Get the payer ID from PayPal.
    $plugin = \Drupal::service('plugin.manager.uc_payment.method')->createFromOrder($order);

    $response = $plugin->sendNvpRequesttwo([
      'METHOD' => 'GetExpressCheckoutDetails',
      'TOKEN' => $session->get('TOKEN'),
    ]);

    $shipping = 0;
    if (is_array($order->line_items)) {
      foreach ($order->line_items as $item) {
        if ($item['type'] == 'shipping') {
          $shipping += $item['amount'];
        }
      }
    }

    $tax = 0;
    if (\Drupal::moduleHandler()->moduleExists('uc_tax')) {
      foreach (uc_tax_calculate($order) as $tax_item) {
        $tax += $tax_item->amount;
      }
    }

    $subtotal = $order->getTotal() - $tax - $shipping;

    $response = $plugin->sendNvpRequesttwo([
      'METHOD' => 'DoExpressCheckoutPayment',
      'TOKEN' => $_GET['token'],
      'PAYMENTACTION' => "Sale",
      'PAYERID' => $_GET['PayerID'],
      'AMT' => uc_currency_format($order->getTotal(), FALSE, FALSE, '.'),
      'DESC' => $this->t('Order @order_id at @store', ['@order_id' => $order->id(), '@store' => uc_store_name()]),
      'INVNUM' => $order->id() . '-' . REQUEST_TIME,
      'BUTTONSOURCE' => 'Ubercart_ShoppingCart_EC_US',
      'NOTIFYURL' => Url::fromRoute('uc_paypal_plus.ipn', [], ['absolute' => TRUE])->toString(),
      'ITEMAMT' => uc_currency_format($subtotal, FALSE, FALSE, '.'),
      'SHIPPINGAMT' => uc_currency_format($shipping, FALSE, FALSE, '.'),
      'TAXAMT' => uc_currency_format($tax, FALSE, FALSE, '.'),
      'CURRENCYCODE' => $order->getCurrency(),
    ]);


    $session->set('PAYERID', $_GET['PayerID']);
    // Immediately complete the order.

    $plugin->orderSubmit($order);

    // Redirect to the order completion page.
    $session->remove('uc_checkout_review_' . $order->id());
    $session->set('uc_checkout_complete_' . $order->id(), TRUE);
    return $this->redirect('uc_cart.checkout_complete');
  }

  /**
   * Handles the review page for PPP Express Checkout Shortcut Flow.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|array
   *   A redirect to the cart or a build array.
   */
  public function ecReview() {
    $session = \Drupal::service('session');
    if (!$session->has('TOKEN') || !($order = Order::load($session->get('cart_order')))) {
      $session->remove('cart_order');
      $session->remove('TOKEN');
      $session->remove('PAYERID');
      drupal_set_message($this->t('An error has occurred in your PayPal PPP payment. Please review your cart and try again.'));
      return $this->redirect('uc_cart.cart');
    }

    // Get the payer ID from PayPal.
    $plugin = \Drupal::service('plugin.manager.uc_payment.method')->createFromOrder($order);
    $response = $plugin->sendNvpRequesttwo([
      'METHOD' => 'GetExpressCheckoutDetails',
      'TOKEN' => $session->get('TOKEN'),
    ]);
    $session->set('PAYERID', $response['PAYERID']);

    // Store delivery address.
    $address = $order->getAddress('delivery');
    $shipname = $response['SHIPTONAME'];
    if (strpos($shipname, ' ') > 0) {
      $address->first_name = substr($shipname, 0, strrpos(trim($shipname), ' '));
      $address->last_name = substr($shipname, strrpos(trim($shipname), ' ') + 1);
    }
    else {
      $address->first_name = $shipname;
      $address->last_name = '';
    }
    $address->street1 = $response['SHIPTOSTREET'];
    $address->street2 = isset($response['SHIPTOSTREET2']) ? $response['SHIPTOSTREET2'] : '';
    $address->city = $response['SHIPTOCITY'];
    $address->zone = $response['SHIPTOSTATE'];
    $address->postal_code = $response['SHIPTOZIP'];
    $address->country = $response['SHIPTOCOUNTRYCODE'];
    $order->setAddress('delivery', $address);

    // Store billing details.
    $address = $order->getAddress('billing');
    $address->first_name = $response['FIRSTNAME'];
    $address->last_name = $response['LASTNAME'];
    $address->country = $response['COUNTRYCODE'];
    $order->setAddress('billing', $address);
    $order->setEmail($response['EMAIL']);

    $order->save();

    $build['instructions'] = array(
      '#markup' => $this->t("Your order is almost complete! Please fill in the following details and click 'Continue checkout' to finalize the purchase."),
    );

    $build['form'] = $this->formBuilder()->getForm('\Drupal\uc_paypal_plus\Form\EcReviewplusForm', $order);

    return $build;
  }

}
