<?php

/** 
 * @file
 * Contains \Drupal\uc_gc_client\Controller\GoCardlessPartner
 */

namespace Drupal\uc_gc_client\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use GuzzleHttp\Exception\RequestException;

/**
 * Functions for communicating with the GoCardless partner: Seamless-CMS.co.uk.
 */
class GoCardlessPartner extends ControllerBase {

  /**
   *
   */
  public function __construct() {
    $this->settings = $this->getSettings();
  }

  /**
   *
   */
  public static function getPartnerWebhook() {
    $settings = GoCardlessPartner::getSettings();
    $settings['sandbox'] ? $ext = '_sandbox' : $ext = '_live';
    return $settings['partner_webhook' . $ext];
  }

  /**
   *
   */
  public static function getSettings() {
    $config_id = \Drupal::state()->get('uc_gc_client_config_id');
    $settings = \Drupal::config($config_id)->get('settings');
    $default_settings = \Drupal::config('uc_gc_client.settings');
    $settings['config_id'] = explode('.', $config_id)[2];
    $default_settings->get('sandbox') ? $settings['sandbox'] = 1 : $settings['sandbox'] = 0;
    $default_settings->get('sandbox') ? $ext = '_sandbox' : $ext = '_live';
    $settings['partner_user'] = $default_settings->get('partner_user' . $ext);
    $settings['partner_pass'] = $default_settings->get('partner_pass' . $ext);
    $settings['org_id'] = $default_settings->get('org_id' . $ext);
    $settings['partner_url'] = $default_settings->get('partner_url');
    return $settings;
  }

  /**
   * Handles session authentication with GC Partner site.
   */
  protected function authenticate() {

    //unset($_SESSION['gc_client_cookie_created']);
    if (isset($_SESSION['gc_client_cookie_created']) && $_SESSION['gc_client_cookie_created'] < REQUEST_TIME - 1800) {
      unset($_SESSION['gc_client_cookie']);
      unset($_SESSION['gc_client_cookie_created']);
    }

    if (!isset($_SESSION['gc_client_cookie_created'])) {
      $user_name = $this->settings['partner_user'];
      $user_pass = $this->settings['partner_pass'];

      // Attempt session authentication if user name and password set.
      if (isset($user_name) && isset($user_pass)) {

        $data = [
          'username' => $user_name,
          'password' => $user_pass,
        ];
        $data = json_encode($data);

        $uri = $this->settings['partner_url'] . '/gc_connect/user/login';
        try {
          $result = \Drupal::httpClient()
            ->post($uri, [
              'headers' => ['Content-Type' => 'application/json'],
              'body' => $data,
            ]
          );
          $result_data = (string) $result->getBody();
          if (empty($result_data)) {
            return FALSE;
          }
          $result_data = json_decode($result_data);
        }
        catch (RequestException $e) {
          return FALSE;
        }

        if ($result->getStatusCode() == 200) {

          // Get X-CRSF token, and save cookie and token.
          $_SESSION['gc_client_cookie'] = $result_data->session_name . '=' . $result_data->sessid;
          $_SESSION['gc_client_cookie_created'] = REQUEST_TIME;

          $xcrf_uri = $this->settings['partner_url'] . '/services/session/token';
          try {
            $xcrf_result = \Drupal::httpClient()
              ->get($xcrf_uri, [
                'headers' => ['Cookie' => $_SESSION['gc_client_cookie']],
              ]
            );
            $xcrf_result_data = (string) $xcrf_result->getBody();
            if (empty($xcrf_result_data)) {
              return FALSE;
            }
            $_SESSION['gc_client_token'] = $xcrf_result_data;
          }
          catch (RequestException $e) {
            return FALSE;
          }
        }
        return $result->getStatusCode();
      }
      else {
        return $result = 'User name and password not set';
      }
    }
    else {
      // Already logged in.
      return $result = 200;
    }
  }

  /**
   * Performs a GET request on the the Partner site.
   */
  public function get($data = NULL) {

    $auth = $this->authenticate();
    if ($auth != 200) {
      return $auth;
    }

    $this->settings['sandbox'] ? $data['environment'] = 'SANDBOX' : $data['environment'] = 'LIVE';

    $headers = [
      'Content-Type' => 'application/json',
      'Cookie' => $_SESSION['gc_client_cookie'],
      'X-CSRF-Token' => $_SESSION['gc_client_token'],
    ];

    $data = json_encode($data);
    $uri = $this->settings['partner_url'] . '/gc/client/' . $this->settings['org_id'];
    try {
      $response = \Drupal::httpClient()->get($uri, [
        'headers' => $headers,
        'body' => $data,
      ]);
      $response_data = (string) $response->getBody();
      if (empty($response_data)) {
        return FALSE;
      }
      return $response_data = json_decode($response_data);
    }
    catch (RequestException $e) {
      return FALSE;
    }
  }

  /**
   * Callback function: Saves key variables for connecting with Partner site.
   *
   * Variables are posted here from Partner site, following completion of
   * GoCardless OAuth flow.
   */
  public static function connect() {

    if (isset($_POST['environ'])) {

      $settings = \Drupal::service('config.factory')->getEditable('uc_gc_client.settings');
      $_POST['environ'] == 'SANDBOX' ? $ext = '_sandbox' : $ext = '_live';

      if (isset($_POST['id'])) {
        $settings->set('org_id' . $ext, $_POST['id'])->save();
      }
      if (isset($_POST['name'])) {
        $settings->set('partner_user' . $ext, $_POST['name'])->save();
      }
      if (isset($_POST['pass'])) {
        $settings->set('partner_pass' . $ext, $_POST['pass'])->save();
      }
    }
    return new Response();
  }

  /**
   * Redirects user to GC settings page upon completion of OAuth flow.
   */
  public static function connectComplete() {

    if (isset($_GET['status'])) {

      if ($_GET['status'] == 'insecure') {
        drupal_set_message(t('Connection cannot be created because site must be secure (https) to use LIVE environment'), 'error');
      }
      elseif ($_GET['status'] == 'connected') {
        drupal_set_message(t('You have connected successfully with GoCardless'));
      }
    }

    $config_id = \Drupal::state()->get('uc_gc_client_config_id');
    $config_id = explode('.', $config_id)[2];
    return new RedirectResponse('/admin/store/config/payment/method/' . $config_id);
  }

  /**
   * Handles API posts to GC Partner site, and optionally logs results.
   */
  public function api($params) {

    $auth = $this->authenticate();
    if ($auth == 200) {
      $result = $this->post($params);
      if (isset($result->error)) {
        $message = t('Error code @code (@error)', ['@code' => $result->code, '@error' => $result->error]);
        drupal_set_message($message, 'error');
        if (\Drupal::config('uc_gc_client.settings')->get('uc_gc_client_debug_api')) {
          \Drupal::logger("uc_gc_client")->error('<pre>' . $message . '<br />' . print_r($result, TRUE) . '</pre>', []);
        }
        return $error = $message;
      }
      else {
        if (\Drupal::config('uc_gc_client.settings')->get('uc_gc_client_debug_api')) {
          \Drupal::logger("uc_gc_client")->notice('<pre>GoCardless API response: <br />' . print_r($result, TRUE) . '</pre>', []);
        }
        return $result;
      }
    }
    else {
      drupal_set_message(t('Error @code connecting with partner site', ['@code' => $auth]), 'error');
      if (\Drupal::config('uc_gc_client.settings')->get('uc_gc_client_debug_api')) {
        \Drupal::logger("uc_gc_client")->error('<pre>' . print_r($auth, TRUE) . '</pre>', []);
      }
      return $auth;
    }
  }

  /**
   * Handles POST requests to GC Partner site.
   */
  private function post($params) {

    // To ensure that it uses the correct configuration object.
    // variable_set('date_format_gocardless', 'Y-m-d');.
    $headers = [
      'Content-Type' => 'application/json',
      'Cookie' => $_SESSION['gc_client_cookie'],
      'X-CSRF-Token' => $_SESSION['gc_client_token'],
    ];
    $this->settings['sandbox'] ? $params['environment'] = 'SANDBOX' : $params['environment'] = 'LIVE';

    $params = json_encode($params);
    $uri = $this->settings['partner_url'] . '/gc/client/' . $this->settings['org_id'];

    try {
      $response = \Drupal::httpClient()
        ->post($uri, [
          'headers' => $headers,
          'body' => $params,
        ]
      );
      $body = (string) $response->getBody();
      if (empty($body)) {
        return FALSE;
      }
      return $body = json_decode($body);
    }
    catch (RequestException $e) {
      return FALSE;
    }

    if (isset($response->error)) {
      return $response;
    }
  }
}
