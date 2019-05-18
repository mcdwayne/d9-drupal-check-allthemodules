<?php

/**
 * @file
 * Contains \Drupal\acquia_cloud_dashboard\CloudAPIHelper
 */

namespace Drupal\acquia_cloud_dashboard;

use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Component\Utility\Json;

class CloudAPIHelper {
  public $username;
  public $password;
  public $invalidCredentials;
  public $notAuthorizedResponse;

  /**
   * Construct the helper function and verify whether the credentials are provided.
   */
  public function __construct() {
    $this->username = \Drupal::config('acquia_cloud_dashboard.settings')->get('username');
    $this->password = \Drupal::config('acquia_cloud_dashboard.settings')->get('password');
    $this->notAuthorizedResponse = "Not authorized";

    $this->verifyCredentialsExist();
  }

  /**
   * Verify that the username and password exist, otherwise redirect to the configuration page.
   * @todo: Add an additional option to verify via a Cloud API call when the user saves their credentials.
   */
  public function verifyCredentialsExist() {
    if (!$this->username || !$this->password) {
      drupal_set_message(t('Please configure your Cloud API credentials on the <a href="@url">settings page</a>.', array('@url' => '/admin/config/cloud-api/configure')), 'warning');
    }
  }

  /**
   * Helper method that makes the Curl Calls (GET).
   */
  public function callMethod($method) {
    $url = 'https://cloudapi.acquia.com/v1/' . $method . '.json';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_USERPWD, $this->username . ':' . $this->password);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $server_output = curl_exec($ch);
    curl_close($ch);
    $decoded_output = Json::decode($server_output);

    // Set a flag in the settings if the API returns not authorized.
    $notAuthorized = (isset($decoded_output['message']) && $decoded_output['message'] == $this->notAuthorizedResponse);
    \Drupal::config('acquia_cloud_dashboard.settings')
        ->set('invalid_credentials', $notAuthorized)
        ->save();

    if ($notAuthorized) {
      drupal_set_message(t('Your cloud credentials look incorrect. Corrrect them on the <a href="@config">settings page</a>.', array('@config' => url('/admin/config/cloud-api/configure'))), 'error');
    } else {
      return $decoded_output;
    }
  }

  /**
   * Helper function that makes the curl calls (POST).
   */
  public function postMethod($method, $request = "POST", $binary = FALSE, $post_data = array(), $params = array()) {
    $url = 'https://cloudapi.acquia.com/v1/' . $method . '.json';

    if (count($params)) {
      $url .= "?";
      foreach ($params as $key => $value) {
        $en_key = urlencode($key);
        $en_val = urlencode($value);
        $url .= ("$en_key=$en_val&");
      }
    }
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_USERPWD, $this->username . ':' . $this->password);

    // Set the url, number of POST vars, POST data.
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 0);
    curl_setopt($ch, CURLOPT_POSTFIELDS, Json::encode($post_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request);
    if ($binary) {
      curl_setopt($ch, CURLOPT_BINARYTRANSFER, TRUE);
    }

    curl_exec($ch);
    curl_close($ch);
    drupal_set_message(t('Command Sent to Cloud API'));
  }
}