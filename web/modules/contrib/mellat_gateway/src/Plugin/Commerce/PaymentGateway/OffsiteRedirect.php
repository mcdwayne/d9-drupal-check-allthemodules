<?php

namespace Drupal\commerce_mellat\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Url;

/**
 * Provides the Off-site Redirect payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "commerce_mellat_redirect",
 *   label = " Mellat (Off-site redirect)",
 *   display_label = "Mellat",
 *    forms = {
 *     "offsite-payment" =
 *   "Drupal\commerce_mellat\PluginForm\OffsiteRedirect\PaymentOffsiteForm",
 *   },
 *   payment_method_types = {"credit_card"},
 * )
 */
class OffsiteRedirect extends OffsitePaymentGatewayBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'merchant_code' => 'Enter your Merchant Code',
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['ml_terminal'] = [
      '#type' => 'textfield',
      '#title' => t('Terminal ID'),
      '#default_value' => $this->configuration['ml_terminal'],
      '#description' => t('The oprational terminal code which bank has gave it to you.'),
      '#required' => TRUE,
    ];
		
	$form['uc_ml_username'] = [
	  '#type' => 'textfield',
	  '#title' => t('Username'),
	  '#default_value' =>  $this->configuration['uc_ml_username'],
	  '#required' => TRUE, 
	  '#description' => t('The username code for Mellat gateway.'),
	];

	$form['uc_ml_password'] = [
	  '#type' => 'textfield',
	  '#title' => t('Password'),
	  '#default_value' => $this->configuration['uc_ml_password'],
	  '#required' => TRUE,
	  '#description' => t('The password code for Mellat gateway.'),
	];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      // Save configuration
      $this->configuration['ml_terminal'] = $values['ml_terminal'];
	  $this->configuration['uc_ml_username'] = $values['uc_ml_username'];
	  $this->configuration['uc_ml_password'] = $values['uc_ml_password'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
	$authority = $request->request->get('RefId');
	$terminal_id = $this->configuration['ml_terminal'];
	$username = $this->configuration['uc_ml_username'];
	$password = $this->configuration['uc_ml_password'];
	

    // Prevents double spending:
    // If a bad manner user have a successfull transaction and want
    // to have another payment with previous trans_id, we must prevent him/her.
    $query = \Drupal::entityQuery('commerce_payment')
      ->condition('remote_state', $authority);
    $payments = $query->execute();
    if (count($payments) > 0) {
      \Drupal::logger('commerce_mellat')
        ->error('Mellat: Double spending occured on order <a href="@url">%order</a> from ip @ip', [
          '@url' => Url::fromUri('base:/admin/commerce/orders/' . $order->id())
            ->toString(),
          '%order' => $order->id(),
          '@ip' => $order->getIpAddress(),
        ]);
      drupal_set_message('Double spending occured.', 'error');
      /** @var \Drupal\commerce_checkout\Entity\CheckoutFlowInterface $checkout_flow */
      $checkout_flow = $order->checkout_flow->entity;
      $checkout_flow_plugin = $checkout_flow->getPlugin();
      $redirect_step = $checkout_flow_plugin->getPreviousStepId('payment');
      $checkout_flow_plugin->redirectToStep($redirect_step);
    }

	$url = 'https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl';   

    if ($request->request->get('ResCode') == '0') {
      $url = 'https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl';
	  $namespace='http://interfaces.core.sw.bps.com/';

      $client = new \SoapClient($url, ['encoding' => 'UTF-8']);
	
	  $parameters = array(
	    'terminalId' => $terminal_id,
	    'userName' => $username,
	    'userPassword' => $password,
	    'orderId' => $order->id(),
	    'saleOrderId' => $request->request->get('SaleOrderId'),
	    'saleReferenceId' => $request->request->get('SaleReferenceId')
	  ); 
	  
      $result = $client->bpVerifyRequest($parameters, $namespace);
	  
      if ($result->return == '0') {
		$result = $client->bpSettleRequest($parameters, $namespace); 
		
		if ($result->return == '0') {		
			$payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
			$payment = $payment_storage->create([
			  'state' => 'completed',
			  'amount' => $order->getTotalPrice(),
			  'payment_gateway' => $this->entityId,
			  'order_id' => $order->id(),
			  'test' => 0,
			  'remote_state' => $authority,
			  'remote_id' => $parameters['saleReferenceId'],
			  'authorized' => $this->time->getRequestTime(),
			]);
			$payment->save();
			drupal_set_message($this->t('Payment was processed'));
		}
      }
      else {
        drupal_set_message($this->t('Transaction failed. Status:') . $result->return);
      }
    }
    else {
      drupal_set_message($this->t('User cancelled transaction'));
    }
  }
}
