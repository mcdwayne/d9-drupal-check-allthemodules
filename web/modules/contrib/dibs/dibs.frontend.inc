<?php
/**
 * @file
 * Front end functions for the DIBS module
 */


/**
 * Form builder; Building the cancel form
 *
 * @param array $form_state
 * @param array $settings
 * @param array $data
 *
 * @return array
 *
 * @ingroup forms
 * @see dibs_frontend_cancel_form_submit()
 */
function dibs_frontend_cancel_form($node, &$form_state = array(), $settings, $data) {
  $form['hash'] = array('#type' => 'hidden', '#value' => $data['api_hash']);
  $form['submit'] = array('#type' => 'submit', '#value' => t('Return to DIBS payment'));
  return $form;
}

/**
 * Process the cancel form
 *
 * @param array $form
 * @param array $form_state
 *
 * @ingroup forms
 * @see dibs_frontend_cancel_form()
 */
function dibs_frontend_cancel_form_submit($form, &$form_state) {
  $hash = $form_state['values']['hash'];

  // Loading the transaction
  $data = dibs_transaction_load($hash, 'hash');
  $settings = dibs_load_settings($data['api_module'], $data['api_delta'], TRUE);

  // Make changes to order id if dictated by retry policy
  $retry = isset($settings['general']['retry_handling']) ? $settings['general']['retry_handling'] : 'new_order_id';
  switch ($retry) {
    case 'new_order_id':
      // Generating order id
      $order_id = dibs_get_next_order_id($data['api_module'], $data['api_delta']);
      // Updating transaction
      $updated = db_update('dibs_transactions')
        ->fields(array(
          'payment_order_id' => $order_id,
          ))
        ->condition('api_hash', $hash)
        ->execute();
      break;
    case 'add_retry_suffix':
      $suffixed = array(
        'api_hash' => $hash,
        'payment_retry_count' => $data['payment_retry_count']+1,
      );
      \Drupal::database()->merge('dibs_transactions')->fields($suffixed)->key(array('api_hash'))->execute();
  }

  // Redirecting to the payment redirect page
  drupal_goto('payment/dibs/redirect/' . $hash);

  exit;
}

/**
 * Utility function to check if the $_REQUEST array has the needed transact
 * keys to perform validation.
 *
 * @param $request
 *    The $_REQUEST array
 * @param $split
 *    The number of payments the order is split into.
 *
 * @return boolean if the request is valid.
 */
function dibs_frontend_validate_transact($request, $split = 1) {
  if ($split == 1) {
    // Mobile payments uses the transaction key.
    return !empty($request['transact']) || !empty($request['transaction']);
  }
  foreach (range(1, $split) as $key) {
    if (!isset($request['transact' . $key]) || !isset($request['transaction' . $key])) {
      return FALSE;
    }
  }
  return TRUE;
}

/**
 * DIBS payment ok callback
 *
 * @param array $transaction
 */
function dibs_frontend_dibs_callback_ok($transaction) {
  $split = $transaction['split_payments'];
  if (dibs_frontend_validate_transact($_REQUEST, $split)) {
    $paytype = isset($_REQUEST['paytype']) ? $_REQUEST['paytype'] : '';
    if (!empty($_REQUEST['cardTypeName'])) {
      $paytype = $_REQUEST['cardTypeName'];
    }
    // No split payments
    if ($split == 1) {
      // In the old API transact key is used, in the new API transaction key
      // is used. We should be able to handle both.
      if (!empty($_REQUEST['transact'])) {
        $transact = $_REQUEST['transact'];
        $transact_key = 'transact='. $transact;
      }
      elseif (!empty($_REQUEST['transaction'])) {
        $transact = $_REQUEST['transaction'];
        $transact_key = 'transaction='. $transact;
      }
    }
    // Split paymebts
    else {
      $lookup_key = !isset($request['transact' . $key]) ? 'transact' : 'transaction';
      $transact = '';
      $transact_a = array();
      $transact_key = '';
      foreach (range(1, $split) as $key) {
        $transact_a[$key] = $_REQUEST[$lookup_key . $key];
        if (empty($transact_key)) {
          $transact_key .= $lookup_key . '1=' . $_REQUEST[$lookup_key . $key];
        }
        else {
          $transact_key .= '&' . $lookup_key . $key . '=' . $_REQUEST[$lookup_key . $key];
        }
      }
    }
    if (!empty($transaction)) {
      $settings = dibs_load_settings($transaction['api_module'], $transaction['api_delta']);
      $test = FALSE;
      // Test MD5 or HMAC depending on what we got.
      if (!empty($settings['general']['md5'])) {
        if (!empty($_REQUEST['authkey'])) {
          $amount = $transaction['payment_price'] * 100 ;
          if (isset($_REQUEST['fee'])) {
            $amount += $_REQUEST['fee'];
          }
          $md5_key = md5($settings['general']['md5_key2'] . md5($settings['general']['md5_key1'] . $transact_key .'&amount='. $amount .'&currency='. $transaction['payment_currency']));
          $test = $md5_key == $_REQUEST['authkey'];
        }
        elseif (!empty($_REQUEST['MAC'])) {
          $hmac_data = $_REQUEST;
          unset($hmac_data['MAC']);
          $test = dibs_generate_hmac_key($hmac_data, $settings['general']['hmac_key']) == $_REQUEST['MAC'];
        }
      }

      // We need to pass the test or not do the test at all.
      if ($test || empty($settings['general']['md5'])) {
        // Updating transaction record with payment transaction id
        $update = array(
          'payment_status' => 1,
          'payment_type' => $paytype,
        );
        if (isset($_REQUEST['fee'])) {
            $update['payment_fee'] = $_REQUEST['fee'];
          }
        if ($split == 1) {
          $update['payment_transaction_id'] = $transact;
        }
        $updated = db_update('dibs_transactions')
          ->fields($update)
          ->condition('tid', $transaction['tid'])
          ->execute();
        if ($split > 1) {
          foreach ($transact_a as $delta => $payment_transaction_id) {
            db_insert('dibs_split_payments')
              ->fields(array(
                'tid' => $transaction['tid'],
                'payment_transaction_id' => $payment_transaction_id,
                'delta' => $delta,
              ))
              ->execute();
          }
        }

        if ($updated) {
          \Drupal::logger('dibs')->info('Transaction #@tid validated as OK.', array(
            '@tid' => $transaction['tid'],
          ));
        }

        // Reload the transaction.
        $transaction = dibs_transaction_load($transaction['tid']);

        // Invoking the module that implemented the payment
        $function = $transaction['api_module'] . '_dibsapi';
        if (function_exists($function)) {
          $function('transaction_callback', $transaction['api_delta'], $transaction);
        }
      }
      else {
        \Drupal::logger('dibs')->warning("MD5 key didn't match for transaction, got @md5_in expected @md5_calc", array(
          '@md5_in' => $authkey,
          '@md5_calc' => $md5_key,
        ));
      }
    }
  }
  else {
    \Drupal::logger('dibs')->error('Transaction not set in request data for OK callback, transaction #@tid.', array(
      '@tid' => $transaction['tid'],
    ));
  }
}
