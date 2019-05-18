<?php

namespace Drupal\commerce_mellat\PluginForm\OffsiteRedirect;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class PaymentOffsiteForm extends BasePaymentOffsiteForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    $order = $payment->getOrder();
    $order_id = $order->id();

    $redirect = Url::fromUri('base:/checkout/' . $order_id . '/payment/return/', ['absolute' => TRUE])
      ->toString();
    $amount = (int) $payment->getAmount()->getNumber();
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    $gateway_configuration = $payment_gateway_plugin->getConfiguration();
    $mode = $gateway_configuration['mode'];
	$terminal_id = $gateway_configuration['ml_terminal'];
	$username = $gateway_configuration['uc_ml_username'];
	$password = $gateway_configuration['uc_ml_password'];
	$local_date  = date("Ymd");
	$local_time  = date("His");
    // Checks if we are in debug mode.

	$url = 'https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl';
    $namespace='http://interfaces.core.sw.bps.com/';

    $client = new \SoapClient($url, ['encoding' => 'UTF-8']);
	
	$parameters = array(
		'terminalId' => $terminal_id,
		'userName' => $username,
		'userPassword' => $password,
		'orderId' => $order_id,
		'amount' => (int) $amount,
		'localDate' => $local_date,
		'localTime' => $local_time, 
		'additionalData' => $order->getStore()->label(),
		'callBackUrl' => $redirect,
		'payerId' => 0,
	);

    $result = $client->bpPayRequest($parameters, $namespace);
	$res = explode (',',$result->return);
	$res_code = $res[0];
	if ($res_code == "0") {
		$bank_result['authority'] = $res[1];
		$bank_result['status'] = $res_code;
	}


    $redirect_url = 'https://bpm.shaparak.ir/pgwchannel/startpay.mellat';
   
    if ($res_code == "0") {
      $redirect_method = 'post';
      $data = [
	    'RefId' => $bank_result['authority']
	  ];
      return $this->buildRedirectForm($form, $form_state, $redirect_url, $data, $redirect_method);
    }
    else {
      drupal_set_message('Error: ' . $res_code, 'error');
      $chekout_page = Url::fromUri('base:/checkout/' . $order_id . '/review', ['absolute' => TRUE])
        ->toString();
      return $this->buildRedirectForm($form, $form_state, $chekout_page, [], NULL);
    }
  }
}
