<?php

namespace Drupal\worldcore\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * WorldCore redirect form.
 */
class SciForm extends FormBase {

  /**
   * Internal function.
   */
  public function getFormId() {
    // Unique ID of the form.
    return 'worldcore_merchant_form';
  }

  /**
   * Worldcore payment form.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $worldcore_payment_id = NULL) {

    $worldcore_payment_id = \Drupal::request()->get('worldcore_payment');

    $global_config = \Drupal::config('system.site');

    $result = db_query('SELECT * FROM {wc_payments} WHERE pid=' . (int) $worldcore_payment_id);

    $payment = $result->fetchAssoc();

    $config = \Drupal::config('worldcore.settings');

    $post_str = json_encode([
      'account' => $payment['merchant_account'],
      'amount' => round($payment['amount'], 2),
      'invoiceId' => $payment['pid'],
      'customField' => $payment['memo'],
    ]);

    $hash_in = strtoupper(hash('sha256', $post_str . $config->get('worldcore_api_password')));
    $auth_header = 'Authorization: wauth key=' . $config->get('worldcore_api_key') . ', hash=' . $hash_in;
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, 'https://api.worldcore.eu/v1/merchant');
    curl_setopt($curl, CURLOPT_HEADER, TRUE);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json; charset=utf-8', $auth_header]);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $post_str);
    curl_setopt($curl, CURLOPT_TIMEOUT, 10);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);

    $curl_response = curl_exec($curl);
    if ($curl_response == FALSE) {

      $error_msg = curl_error($curl);

      \Drupal::logger('WorldCore')->error('CURL error: ' . $error_msg);

      return;

    }
    else {

      list($response_headers, $json_response) = explode("\r\n\r\n", $curl_response, 2);
      preg_match("/^WSignature: ([A-Z0-9]{64})\r$/m", $response_headers, $hash_outputed);
      $hash_check = strtoupper(hash('sha256', $json_response . $config->get('worldcore_api_password')));
      if ($hash_outputed[1] != $hash_check) {

        \Drupal::logger('WorldCore')->error('Hash not match!');

        return;

      }
      else {
        $decoded_response = json_decode($json_response, TRUE);
        if (isset($decoded_response['error'])) {

          \Drupal::logger('WorldCore')->error('Error occurred: ' . print_r($decoded_response['error']));

          return;

        }
      }
      curl_close($curl);

      // Interface data:
      $form['payment_id'] = [
        '#type' => 'label',
        '#title' => $this->t('Order #') . ' ' . $payment['pid'],
        '#value' => '',
      ];

      $form['amount'] = [
        '#type' => 'item',
        '#title' => $this->t('Amount') . ' ' . round($payment['amount'], 2) . ' ' . $payment['currency'],
      ];

      $form['memo'] = [
        '#type' => 'item',
        '#title' => $this->t('Memo') . ' ' . $payment['memo'],
        '#value' => '',
      ];

      // SCI data...
      $form['#action'] = $decoded_response['data']['url'];

      $form['#method'] = 'get';

      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Pay now'),
      ];

    }

    return $form;

  }

  /**
   * Worldcore form validation.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * Worldcore payment form processing.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
