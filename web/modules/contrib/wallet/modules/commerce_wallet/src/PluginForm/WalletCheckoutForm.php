<?php

namespace Drupal\commerce_wallet\PluginForm;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\commerce_order\Entity\Order;


class WalletCheckoutForm extends BasePaymentOffsiteForm {

		/**
		 * {@inheritdoc}
		 */
		public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
				$form = parent::buildConfigurationForm($form, $form_state);
				/** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
				$payment = $this->entity;

				$redirect_method = 'post';
				$order_id = \Drupal::routeMatch()->getParameter('commerce_order')->id();
				$order = Order::load($order_id);
				$user_id = \Drupal::currentUser()->id();
				$callback_url =  Url::FromRoute('commerce_payment.checkout.return', ['commerce_order' => $order_id, 'step' => 'payment'],array('absolute' => TRUE))->toString();
				$cancel_url   =  Url::FromRoute('commerce_payment.checkout.cancel',['commerce_order' => $order_id, 'step' => 'payment'],array('absolute' => TRUE))->toString();
				$paramList["ORDER_ID"] = $order_id;
				$paramList["CUST_ID"] = $user_id;
				$paramList["TXN_AMOUNT"] = round($payment->getAmount()->getNumber(), 2);
				$paramList["CALLBACK_URL"] = $callback_url;
				$paramList['CANCEL_URL']   = $cancel_url;
 				$redirect_url = Url::FromRoute('commerce_wallet.commerceWalletDeductPayment')->toString();;
				return $this->buildRedirectForm($form, $form_state, $redirect_url, $paramList, 'post');
		}
}
