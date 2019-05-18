<?php

namespace Drupal\commercepayu\PluginForm;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\commerce_order\Entity\Order;
use Drupal\Component\Utility\Crypt;

class PayuCheckoutForm extends BasePaymentOffsiteForm {

		const PAYU_API_TEST_URL = 'https://test.payu.in/_payment';
		const PAYU_API_URL = 'https://secure.payu.in/_payment';
		/**
		 * {@inheritdoc}
		 */
		public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
				$form = parent::buildConfigurationForm($form, $form_state);
				/** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
				$payment = $this->entity;

				$redirect_method = 'post';
				/** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
				$payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();

				$order_id     = \Drupal::routeMatch()->getParameter('commerce_order')->id();
				$order        = Order::load($order_id);
				$user_id      = \Drupal::currentUser()->id();
				$amount       = round($payment->getAmount()->getNumber(), 2);
				$email        = \Drupal::currentUser()->getEmail();
				$name         = \Drupal::currentUser()->getDisplayName();

				$address      = $order->getBillingProfile()->address->first();
				$merchant_id  = $payment_gateway_plugin->getConfiguration()['merchant_id'];
				$merchant_key = $payment_gateway_plugin->getConfiguration()['merchant_key'];
				$salt         = $payment_gateway_plugin->getConfiguration()['salt'];
				$cur          = $payment_gateway_plugin->getConfiguration()['currency'];
				$lng          = $payment_gateway_plugin->getConfiguration()['language'];
				$mode         = $payment_gateway_plugin->getConfiguration()['mode'];
				if ($mode == 'test') {
						$redirect_url = self::PAYU_API_TEST_URL;
				} else {
						$redirect_url = self::PAYU_API_URL;
				}
				$txn_id = $this->generate_random_string(7).'-'.$order_id;
				$callback_url =  Url::FromRoute('commerce_payment.checkout.return', ['commerce_order' => $order_id, 'step' => 'payment'], array('absolute' => TRUE))->toString();
				$paramList['surl']      = $callback_url;
				$paramList['furl']      = $callback_url;
				$paramList['hash']      = $this->generate_hash($merchant_key, $txn_id ,$amount,'Retail', $name, $email ,$salt);
				$paramList['key']       = $merchant_key;
				$paramList['txnid']     = $txn_id;
				$paramList['amount']    = $amount;
				$paramList['firstname'] = $name;
				$paramList['email']     = $email;
				$paramList['productinfo'] = 'Retail';
				$paramList['phone']       = '';

/*				$paramList["MID"] = $merchant_id;
				$paramList["ORDER_ID"] = $order_id;
				$paramList["CUST_ID"] = $user_id;
				$paramList["INDUSTRY_TYPE_ID"] = 'Retail';
				$paramList["CHANNEL_ID"] = 'WEB';
				$paramList["TXN_AMOUNT"] = round($payment->getAmount()->getNumber(), 2);
				$paramList["CALLBACK_URL"] = $callback_url;*/

				return $this->buildRedirectForm($form, $form_state, $redirect_url, $paramList, 'post');
		}

		/**
		 * @param $key
		 * @param $txnid
		 * @param $amount
		 * @param $productinfo
		 * @param $firstname
		 * @param $email
		 * @param $salt
		 * @return string
		 */
		public function generate_hash($key,$txnid,$amount,$productinfo,$firstname,$email,$salt) {
				$hashSeq=$key.'|'.$txnid.'|'.$amount.'|'.$productinfo.'|'.$firstname.'|'.$email.'|||||||||||'.$salt;
				$hash=strtolower(hash("sha512",$hashSeq));
				return $hash;
		}
		function generate_random_string($length = 10) {
				$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
				$charactersLength = strlen($characters);
				$randomString = '';
				for ($i = 0; $i < $length; $i++) {
						$randomString .= $characters[rand(0, $charactersLength - 1)];
				}
				return $randomString;
		}
}
