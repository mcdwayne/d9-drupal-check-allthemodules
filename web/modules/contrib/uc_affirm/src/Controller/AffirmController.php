<?php

/**
 * @file
 * Contains \Drupal\uc_affirm\Controller\AffirmController.
 */

namespace Drupal\uc_affirm\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Serialization\Json;
use Drupal\uc_order\Entity\Order;
use Symfony\Component\HttpFoundation\RedirectResponse;
// use Drupal\Component\Datetime;

/**
 * Utility functions for affirm payment methods.
 */
class AffirmController extends ControllerBase {

  /**
   * Handles the review page for Express Checkout Mark Flow.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect to the cart or cart review page.
   */
  public function uaCancel() {
    $session = \Drupal::service('session');
    if (!($order = Order::load($session->get('cart_order')))) {
      $session->remove('cart_order');
      drupal_set_message($this->t('Your Affirm payment was cancelled. Please feel free to continue shopping or contact us for assistance.'));
      $obj = new RedirectResponse(\Drupal::url('uc_cart.cart'));
      return $obj->send();
    }
    $obj = new RedirectResponse(\Drupal::url(''));
    return $obj->send();
  }

  /**
   * Handles the review page for Express Checkout Mark Flow.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect to the cart or cart review page.
   */
  public function uaAuthentication() {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
      $checkout_token = $_POST["checkout_token"];
    }
    // Exit now if the $_POST was empty.
    if (empty($checkout_token)) {
      drupal_set_message($this->t('Affirm authentication failed!'));
      $obj = new RedirectResponse(\Drupal::url('uc_cart.cart'));
      return $obj->send();
    }

    $data = array(
      'checkout_token' => $checkout_token,
    );
    $order = array();
    return $this->uaProcessTransaction($order, $data);
  }

  /**
   * Process the payment transaction with the info received from Affirm.
   *
   * @param object $order
   *   The loaded order that is being processed.
   * @param array $data
   *   Data to send to Affirm.
   *
   * @return bool
   *   Returns TRUE if the transaction was successful. FALSE if it was not.
   */
  public function uaProcessTransaction($order, $data) {
    // Query to API to ask for a Authorization payment.
    $response = $this->uc_affirm_api_request(UC_AFFIRM_AUTH_ONLY, $order, array(), $data);
    $session = \Drupal::service('session');
    $order = Order::load($session->get('cart_order'));
    $order_id = $order->id();
    if (empty($response)) {
      drupal_set_message($this->t('We could not complete your payment with Affirm. Please try again or contact us if the problem persists.'), 'error');
      return FALSE;
    }
    if (empty($order_id)) {
      drupal_set_message($this->t('Your cart is emplty. please feel free to continue your purchasing!'), 'error');
      $obj = new RedirectResponse(\Drupal::url('uc_cart.cart'));
      return $obj->send();
    }
    /*
     * In case the payment method is configured for Auth & Capture, the capture
     * request is triggered right after the authorization.
     */
    if (isset($response['status']) == 'authorized') {
      $config = $this->config('uc_affirm.settings');
      uc_order_comment_save($order->id(), 0, $this->t('Payment order Initialized through Affirm. Order amount : @amount @currency', array('@amount' => $order->order_total, '@currency' => 'USD')), 'admin', uc_order_state_default('order_under_review'), TRUE);
      // Logging transaction details.
      $this->uc_affirm_transaction_save($order_id, $order->getTotal(), $response['status'], $order->getEmail(), $response['id'], $response['id']);
      if (strtolower(UC_AFFIRM_AUTH_CAPTURE) == strtolower($config->get('uc_affirm_txt_type'))) {
        $response = $this->uc_affirm_api_request(UC_AFFIRM_CAPTURE_ONLY, $order, $response['id'], $data);
        if (empty($response)) {
          drupal_set_message($this->t('We could not complete your payment with Affirm. Please try again or contact us if the problem persists.'));
          return FALSE;
        }
        else {
          if (isset($response['code']) && $response['code'] == 'capture-declined') {
            drupal_set_message($this->t('Affirm amount capture failed, Please try agin !!'));
            $obj = new RedirectResponse(\Drupal::url('uc_cart.cart'));
            return $obj->send();
          }
          else {
            $this->uc_afffirm_transaction_updates($order_id, $response, $status = 'payment_received', $field = 'capture_trans_id');
            uc_order_comment_save($order_id, 0, $this->t('Payment of @amount @currency submitted through Affirm.', array('@amount' => $order->order_total, '@currency' => 'USD')), 'order', uc_order_state_default('payment_received'), TRUE);
            $this->uc_affirm_complete($order_id);
          }
        }
      }
      else {
        if ($response['status'] == 'authorized') {
          db_update('uc_orders')
              ->fields(array(
                'order_status' => 'order_under_review',
              ))
              ->condition('order_id', $order_id, '=')
              ->execute();
          db_update('uc_affirm')
              ->fields(array(
                'status' => 'order_under_review',
              ))
              ->condition('order_id', $order_id, '=')
              ->execute();
        }

        $order_id = intval($session->get('cart_order'));
        $this->uc_affirm_complete($order_id);
      }
    }
    elseif ($response['status'] == 'auth-declined') {
      drupal_set_message(t('Affirm transaction authentication failed, Please try agin !!'), 'error');
      $obj = new RedirectResponse(\Drupal::url('uc_cart.cart'));
      return $obj->send();
    }
    else {
      $this->get_affirm_status($response['status']);
    }
  }

  /**
   * Post data to Affirm using cURL library.
   *
   * @param string $txn_type
   *   The transaction type (Authorization/Capture/Refund/Void).
   * @param object $order
   *   The order object the payment request is being submitted for.
   * @param array $charge_id
   *   The payment method instance array associated with this API request.
   * @param array $data
   *   Data to send to Affirm.
   *
   * @return array
   *   Return a cURL error or an Affirm response.
   */
  public function uc_affirm_api_request($txn_type, $order, $charge_id = NULL, $data = array()) {
    $config = $this->config('uc_affirm.settings');
    $private_key = $config->get('uc_affirm_private_key');
    $public_key = $config->get('uc_affirm_public_key');
    $txn_mode = $config->get('uc_affirm_server');

    $headers = array(
      'Content-Type: application/json',
    );
    // Prepare the CURL options.
    $options = array(
      CURLOPT_HTTPHEADER => $headers,
      CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
      CURLOPT_USERPWD => $public_key . ':' . $private_key,
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_POST => 1,
      CURLOPT_POSTFIELDS => Json::encode($data),
    );

    // Get the API endpoint URL for the method's transaction mode.
    $url = $this->uc_affirm_api_server_url($txn_mode, $txn_type, $charge_id);
    $ch = curl_init($url);
    curl_setopt_array($ch, $options);
    $response = curl_exec($ch);
    curl_close($ch);
    return Json::decode($response);
  }

  /**
   * Returns the URL to the Affirm server determined by the transaction type.
   *
   * @param string $txn_mode
   *   The transaction mode that relates to the production or test server.
   * @param string $txn_type
   *   The transaction type (Authorization/Capture/Refund/Void).
   * @param array $charge_id
   *   The payment method instance array associated with this API request.
   *
   * @return string|bool
   *   The URL to use to submit requests to the Affirm server.
   */
  public function uc_affirm_api_server_url($txn_mode, $txn_type, $charge_id = NULL) {
    // Gets the API endpoint of Affirm server.
    $api_url = $this->uc_affirm_server_url($txn_mode);
    switch ($txn_type) {
      case UC_AFFIRM_AUTH_ONLY:
        return $api_url;

      case UC_AFFIRM_CAPTURE_ONLY:
        if (empty($charge_id)) {
          drupal_set_message($this->t('You can not create a capture request without specifying a transaction object'));
          return FALSE;
        }
        return $api_url . $charge_id . '/capture';

      case UC_AFFIRM_REFUND:
        if (empty($charge_id)) {
          drupal_set_message($this->t('You can not create a credit request without specifying a transaction object'));
          return FALSE;
        }
        return $api_url . $charge_id . '/refund';

      case UC_AFFIRM_VOID:
        if (empty($charge_id)) {
          drupal_set_message($this->t('You can not create a void request without specifying a transaction object'));
          return FALSE;
        }
        return $api_url . $charge_id . '/void';
    }
    return FALSE;
  }

  /**
   * Returns the base URL to Affirm API server depending on the transaction mode.
   *
   * @param string $txn_mode
   *   The transaction mode that relates to the production or test server.
   *
   * @return string|bool
   *   The Base URL to use to submit requests to the Affirm API server.
   */
  public function uc_affirm_server_url($txn_mode) {
    switch ($txn_mode) {
      case UC_AFFIRM_TXN_MODE_LIVE:
        return 'https://api.affirm.com/api/v2/charges/';
      case UC_AFFIRM_TXN_MODE_TEST:
        return 'https://sandbox.affirm.com/api/v2/charges/';
    }
    return FALSE;
  }
  
  /**
   * Custom function to log transaction details.
   *
   * @param int $order_id
   *   Unique id for a order.
   * @param float $amount
   *   The order total amount.
   * @param string $payment_status
   *   The transaction status, that returned from affirm.
   * @param string $buyer_email
   *   The email address of the buyer.
   * @param int $authentication_trans_id
   *   The transaction id, that returned from affirm.
   * @param string $charge_id
   *   The payment transaction returned id.
   *
   * @return bool
   *   Returns TRUE if the transaction was successful. FALSE if it was not.
   */
  public function uc_affirm_transaction_save($order_id, $amount, $payment_status, $buyer_email, $authentication_trans_id, $charge_id = NULL) {
    $table = 'uc_affirm';
    $table_fields = array('order_id',
      'charge_id',
      'amount',
      'status',
      'buyer_email',
      'received_time',
      'authentication_trans_id',
    );
    $field_value = array($order_id,
      $charge_id,
      $amount,
      $payment_status,
      $buyer_email,
      REQUEST_TIME,
      $authentication_trans_id,
    );
    \Drupal::database()->insert($table)->fields($table_fields, $field_value)->execute();
    return TRUE;
  }
  
  /**
   * Function to handle the updations in the order table and uc_affirm table.
   *
   * @param int $order_id
   *   Unique id for a order.
   * @param array $response
   *   The the response data from affirm.
   * @param string $status
   *   Status to be updated in the tables.
   * @param string $field
   *   The fields names for updating the transaction id's.
   * @param float $refund
   *   The refund amount that returned to the customer.
   *
   */
  public function uc_afffirm_transaction_updates($order_id, $response, $status, $field, $refund = NULL) {
    $order = Order::load($order_id);
    db_update('uc_orders')
        ->fields(array(
          'order_status' => $status,
        ))
        ->condition('order_id', $order_id, '=')
        ->execute();

    $uc_affirm_update = db_update('uc_affirm');
      $uc_affirm_update->fields(array(
        'status' => $status,
        $field => $response['transaction_id'],
      ));
      if ($refund != NULL) {
        $uc_affirm_update->fields(array(
          'refund_amount' => $refund,
        ));
      }
      $uc_affirm_update->condition('order_id', $order_id, '=')
          ->execute();

    // Here you need to put in the routines for a successful
    // transaction such as sending an email to customer,
    // setting database status, informing logistics etc.
    $comment = t('Affrim @transaction_id id', array('@transaction_id' => $response['transaction_id']));
    if (($field == "capture_trans_id") || ($field == "refund_trans_id")) {
      $order_amt = 0;
      if ($field == "refund_trans_id") {
        $usd = $response['amount'] / 100;
        $order_amt = '-' . $usd;
      }
      else {
        $order_amt = $order->getTotal();
      }
      uc_payment_enter($order_id, 'Affirm', $order_amt, $order->getOwnerId(), NULL, $comment);
    }
  }
  
  /**
   * Page callback to handle a complete Affirm Payment sale.
   *
   * @param int $order_id
   *   Unique id for a order.
   */
  public function uc_affirm_complete($order_id = 0) {
    $session = \Drupal::service('session');
    if (intval($session->get('cart_order')) != $order_id) {
      $session->set('cart_order', $order_id);
    }

    if (!($order = Order::load($session->get('cart_order')))) {
      $obj = new RedirectResponse(\Drupal::url('uc_cart.cart'));
      return $obj->send();
    }
    // This lets us know it's a legitimate access of the complete page.
    $session->set('uc_checkout_complete_' . $order_id, TRUE);
    $obj = new RedirectResponse(\Drupal::url('uc_cart.checkout_complete'));
    return $obj->send();
  }

  /**
   * Track each response code and show proper messages against them.
   */
  public function get_affirm_status($response_code = NULL) {
    drupal_set_message(t('Transaction failed, Please try agin !!'), 'error');
    $obj = new RedirectResponse(\Drupal::url('uc_cart.cart'));
    return $obj->send();
  }
  
  /**
   * Void handling controller.
   */
  function uc_affirm_void($charge_id = NULL) {
    $response = $this->uc_affirm_api_request(UC_AFFIRM_VOID, $order = array(), $charge_id, $data = array());
    if (!empty($response)) {
      db_update('uc_affirm')
        ->fields(array(
          'status' => 'void',
        ))
        ->condition('charge_id', $charge_id, '=')
        ->execute();
    }
    return $response;
  }

}
