<?php

namespace Drupal\commerce_wallet\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Exception\InvalidRequestException;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_price\Price;
use Drupal\commerce_price\RounderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the Wallet payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "wallet_payment",
 *   label = @Translation("Wallet Payment"),
 *   display_label = @Translation("Wallet"),
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_wallet\PluginForm\WalletCheckoutForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex", "dinersclub", "discover", "jcb", "maestro", "mastercard", "visa",
 *   },
 * )
 */
class WalletCheckout extends OffsitePaymentGatewayBase {

    /**
     * {@inheritdoc}
     */
    public function onReturn(OrderInterface $order, Request $request) {
		    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
		    $payment = $payment_storage->create([
				    'state' => 'authorization',
				    'amount' => $order->getTotalPrice(),
				    'payment_gateway' => $this->entityId,
				    'order_id' => $order->id(),
				    'test' => $this->getMode() == 'test',
				    'remote_id' => $order->id(),
				    'remote_state' => 'Success',
				    'authorized' => REQUEST_TIME,
		    ]);
		    $payment->save();
		    drupal_set_message($this->t('Your payment was successful with Order id : @orderid and Transaction id : @transaction_id', ['@orderid' => $order->id(), '@transaction_id' => $txnid]));
    }
		public function onCancel(OrderInterface $order, Request $request) {
				$payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
				$payment = $payment_storage->create([
						'state' => 'authorization',
						'amount' => $order->getTotalPrice(),
						'payment_gateway' => $this->entityId,
						'order_id' => $order->id(),
						'test' => $this->getMode() == 'test',
						'remote_id' => $order->id(),
						'remote_state' => 'Failed',
						'authorized' => REQUEST_TIME,
				]);
				$payment->save();
				drupal_set_message($this->t('Transaction Failed'), 'error');
		}
}