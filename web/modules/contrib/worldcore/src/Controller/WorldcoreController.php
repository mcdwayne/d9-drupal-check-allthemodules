<?php

namespace Drupal\worldcore\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Worldcore controllers.
 */
class WorldcoreController extends ControllerBase {

  /**
   * Worldcore success page.
   */
  public function success() {

    $filePath = drupal_get_path('module', 'worldcore') . "/templates/page--success.html.twig";

    $f = fopen($filePath, "r");
    $contents = fread($f, filesize($filePath));
    fclose($f);

    return [
      '#content' => $contents,
    ];
  }

  /**
   * Worldcore payment failed page.
   */
  public function fail() {

    $filePath = drupal_get_path('module', 'worldcore') . "/templates/page--fail.html.twig";

    $f = fopen($filePath, "r");
    $contents = fread($f, filesize($filePath));
    fclose($f);

    return [
      '#content' => $contents,
    ];

  }

  /**
   * Worldcore status_url controller.
   */
  public function status() {

    $config = \Drupal::config('worldcore.settings');

    $headers = apache_request_headers();

    $json_body = file_get_contents('php://input');
    $hash_check = strtoupper(hash('sha256', $json_body . $config->get('worldcore_api_password')));
    // Hash checking.
    if ($headers['WSignature'] == $hash_check) {

      // Some code to confirm payment.
      $created = time();

      $decoded_response = json_decode($json_body, TRUE);

      $result = db_query('SELECT * FROM {wc_payments} WHERE pid=' . (int) $decoded_response['invoiceId']);
      $payment = $result->fetchAssoc();

      // Enroll payment.
      $query = \Drupal::database()->update('wc_payments')
        ->fields([
          'track' => $decoded_response['track'],
          'account' => $decoded_response['account'],
          'enrolled' => $created,
        ])
        ->condition('pid', (int) $decoded_response['invoiceId'])
        ->execute();

      // Fire hook.
      $payment['track'] = $decoded_response['track'];
      $payment['account'] = $decoded_response['account'];
      $payment['enrolled'] = $created;
      \Drupal::moduleHandler()->invokeAll('enrolled', $payment['pid'], $payment);

    }
    else {

      // Some code to log invalid payments.
      \Drupal::logger('WorldCore')->error('Hash mismatch! ' . $headers['WSignature'] . ' vs. ' . $hash_check);

      die();

    }

    exit;

  }

  /**
   * Sample settings page.
   */
  public function sample() {

    global $base_path;

    return [
      '#content' => $this->t('<h1>Setting up WorldCore</h1>
		<p><A HREF="https://worldcore.eu/">Login</A> to your WC account, click on <A HREF="https://worldcore.eu/Customer/ApiSettings/Merchant">Settings</A> menu section and fill in the following fields:
		<table cellpadding="5">
		<tbody>
		<tr>
			<td nowrap="nowrap">Status URL:</td>
			<td align="left"><input style="display: inline;" value="@status_url" size="50" id="m_name" name="m_name" type="text"></td>
		</tr>
		<tr>
			<td nowrap="nowrap">Success URL:</td>
			<td align="left"><input style="display: inline;" value="@success_url" size="50" id="m_name" name="m_name" type="text"></td>
		</tr>
		<tr>
			<td nowrap="nowrap">Failure URL:</td>
			<td align="left"><input style="display: inline;" value="@fail_url" size="50" id="m_name" name="m_name" type="text"></td>
		</tr>
		</tbody></table>',
    [
      '@status_url' => 'https://' . $_SERVER['HTTP_HOST'] . $base_path . 'worldcore/status_url',
      '@success_url' => 'https://' . $_SERVER['HTTP_HOST'] . $base_path . 'worldcore/success', '',
      '@fail_url' => 'https://' . $_SERVER['HTTP_HOST'] . $base_path . 'worldcore/fail', '',
    ]),
    ];

  }

}
