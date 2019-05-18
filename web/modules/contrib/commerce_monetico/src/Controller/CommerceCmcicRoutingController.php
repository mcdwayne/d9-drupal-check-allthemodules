<?php

namespace Drupal\commerce_cmcic\Controller;

use Drupal\commerce_cmcic\CommerceCmcicAPI;
use Drupal\commerce_cmcic\kit\CmcicHmac;
use Drupal\commerce_cmcic\kit\CmcicTpe;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_price\Price;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CommerceCmcicRoutingController.
 *
 * @package Drupal\commerce_cmcic\Controller
 */
class CommerceCmcicRoutingController extends ControllerBase {
  public function response() {
    // Get all response from the bank server.
    $payment_data = $_POST;

    \Drupal::logger('commerce_cmcic')
      ->notice('The data received from bank server are @data', array('@data' => var_export($payment_data, TRUE)));

    // Get the order ID.
    list($order_id, $timestamp) = explode('-', $payment_data['reference']);
    if ($order_id) {
      $order = Order::load($order_id);
    }
    else {
      $order = FALSE;
    }

    if (!$order) {
      \Drupal::logger('commerce_cmcic')->notice('The order is undefined.');
      return new Response();
    }

    // Get informations about payment method.
    /** @var \Drupal\commerce_cmcic\Plugin\Commerce\PaymentGateway\CmcicPaymentGateway $payment_gateway_plugin */
    $payment_gateway = $order->get('payment_gateway')->first()->entity;
    $payment_gateway_plugin = $payment_gateway->getPlugin();
    $settings = $payment_gateway_plugin->getConfiguration();

    $settings += CommerceCmcicAPI::getSettings($order);//, $payment_method);

    $settings['url_server'] = CommerceCmcicAPI::getServer($settings['bank_type'], $settings['mode']);

    // TPE init variables.
    $o_tpe = new CmcicTpe($settings);
    $o_hmac = new CmcicHmac($o_tpe);

    // Message Authentication.
    $cgi2_fields = sprintf(
      CMCIC_CGI2_FIELDS,
      $o_tpe->sNumero,
      $payment_data['date'],
      $payment_data['montant'],
      $payment_data['reference'],
      $payment_data['texte-libre'],
      $o_tpe->sVersion,
      $payment_data['code-retour'],
      $payment_data['cvx'],
      $payment_data['vld'],
      $payment_data['brand'],
      $payment_data['status3ds'],
      $payment_data['numauto'],
      isset($payment_data['motifrefus']) ? $payment_data['motifrefus'] : '',
      $payment_data['originecb'],
      $payment_data['bincb'],
      $payment_data['hpancb'],
      $payment_data['ipclient'],
      $payment_data['originetr'],
      $payment_data['veres'],
      $payment_data['pares']
    );

    \Drupal::logger('commerce_cmcic')->notice('The cgi2 fields string are @fields', array('@fields' => $cgi2_fields));

    $currency_code = $order->getTotalPrice()->getCurrencyCode();
    $amount = CommerceCmcicApi::getPriceValue($payment_data['montant']);

    // If there was already a transaction with the same transaction id.
    if (in_array($payment_data['code-retour'], array(
        'Annulation',
        'paiement',
        'payetest'
      )) &&
      !empty($payment_data['MAC']) &&
      $auth = CommerceCmcicApi::historyLoad($payment_data['MAC'])
    ) {

      // Load the prior IPN's transaction and update that with the capture values.
      //$transaction = commerce_payment_transaction_load($auth['transaction_id']);

    }
    else {
      // Create a new payment transaction for the order.
      $payment = Payment::create(array(
        'type' => 'payment_default',
        'payment_gateway' => $payment_gateway,
        'order_id' => $order->id(),
      ));
      //$transaction->instance_id = $payment_method['instance_id'];
    }

    $payment->setRemoteId($payment_data['numauto']);
    $payment->setAmount(new Price($amount, $currency_code));
    //$payment->set->payload[REQUEST_TIME] = $payment_data;

    // Set the transaction's statuses based on the CM-CIC payment_status.
    $payment->setRemoteState($payment_data['code-retour']);

    if ($o_hmac->computeHmac($cgi2_fields) == strtolower($payment_data['MAC'])) {

      switch ($payment_data['code-retour']) {
        case "Annulation":
          // Payment has been refused
          // put your code here (email sending / Database update)
          // Attention : an autorization may still be delivered for this payment.
          $payment->setState('failure');
          //$transaction->message = t('The authorization was voided.');
          break;

        case "payetest":
          // Payment has been accepted on the test server
          // put your code here (email sending / Database update).
          $payment->setState('success');
          //$transaction->message = t('The payment has completed.');
          break;

        case "paiement":
          // Payment has been accepted on the productive server
          // put your code here (email sending / Database update).
          $payment->setState('success');
          //$transaction->message = t('The payment has completed.');
          break;

        /*** ONLY FOR MULTIPART PAYMENT ***/
        case "paiement_pf2":
        case "paiement_pf3":
        case "paiement_pf4":
          // Payment has been accepted on the productive server for the part #N
          // return code is like paiement_pf[#N]
          // put your code here (email sending / Database update)
          // You have the amount of the payment part in $_POST['montantech'].
          break;

        case "Annulation_pf2":
        case "Annulation_pf3":
        case "Annulation_pf4":
          // Payment has been refused on the productive server for the part #N
          // return code is like Annulation_pf[#N]
          // put your code here (email sending / Database update)
          // You have the amount of the payment part in $_POST['montantech'].
          break;

      }

      // Save the transaction information.
      $payment->save();
      $payment_data['transaction_id'] = $payment->id();

      //commerce_payment_redirect_pane_next_page($order);

      \Drupal::logger('commerce_cmcic')->notice('Payment processed for Order @order_number with ID @txn_id.',
        array(
          '@txn_id' => $payment_data['numauto'],
          '@order_number' => $order->getOrderNumber(),
        )
      );

      if (in_array($payment_data['code-retour'], array('paiement', 'payetest'))) {
        $transitions = $order->getState()->getTransitions();
        if (isset($transitions['place'])) {
          $order->getState()->applyTransition($transitions['place']);
          $order->save();
        }
      }

      // Add additional information.
      $payment_data['tpe'] = $o_tpe->sNumero;
      $payment_data['version'] = $o_tpe->sVersion;
      $payment_data['order_id'] = $order->id();

      // Save payment information.
      //CommerceCmcicAPI::saveData($payment_data);

      $receipt = CMCIC_CGI2_MACOK;

    }
    else {
      // Your code if the HMAC doesn't match.
      $receipt = CMCIC_CGI2_MACNOTOK . $cgi2_fields;
    }

    /**************************************/
    /**** Send receipt to CMCIC server ****/
    /**************************************/
    return new Response(sprintf(CMCIC_CGI2_RECEIPT, $receipt));
  }
}
