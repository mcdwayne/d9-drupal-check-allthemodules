<?php

namespace Drupal\accountkit;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Contains all Account Kit related logic.
 */
class AccountKitManager {

  private $configFactory;

  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->configFactory = $configFactory;
  }



  public function getAccessToken() {
    // Initialize variables
    $app_id = $this->getAppId();
    $secret = $this->getAppSecret();
    $version = $this->getApiVersion();

    $code = \Drupal::request()->get('code');

    // Exchange authorization code for access token
    $token_exchange_url = 'https://graph.accountkit.com/' . $version . '/access_token?' .
      'grant_type=authorization_code' .
      '&code=' . $code .
      "&access_token=AA|$app_id|$secret";

    $data = $this->curlit($token_exchange_url);

    if(!empty($data['error'])) {
      drupal_set_message($data['error']['message']
        . " type: ". $data['error']['type']
        . " code: " . $data['error']['code']
        . " fbtrace_id:" . $data['error']['fbtrace_id'],
        "error");
    }

    return $data['access_token'];
  }

  /**
   * Get user information like email or phone.
   *
   * @return array
   *   Array containing user info.
   */
  public function getUserInfo() {
    $data = NULL;
    $access_token = $this->getAccessToken();
    if (!empty($access_token)) {
      // Get Account Kit information
      $me_endpoint_url = 'https://graph.accountkit.com/' . $this->getApiVersion() . '/me?' .
        'access_token=' . $access_token;
      $data = $this->curlit($me_endpoint_url);
    }

    return $data;
  }


  public function curlit($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $data = json_decode(curl_exec($ch), TRUE);
    curl_close($ch);
    return $data;
  }

  /**
   * Returns app_id from module settings.
   *
   * @return string
   *   Application ID defined in module settings.
   */
  public function getAppId() {
    $app_id = $this->configFactory
      ->get('accountkit.settings')
      ->get('app_id');
    return $app_id;
  }

  /**
   * Returns app_secret from module settings.
   *
   * @return string
   *   Application secret defined in module settings.
   */
  public function getAppSecret() {
    $app_secret = $this->configFactory
      ->get('accountkit.settings')
      ->get('app_secret');
    return $app_secret;
  }

  /**
   * Returns api_version from module settings.
   *
   * @return string
   *   API version defined in module settings.
   */
  public function getApiVersion() {
    $api_version = $this->configFactory
      ->get('accountkit.settings')
      ->get('api_version');
    return $api_version;
  }

  /**
   * Returns redirect url from module settings.
   *
   * @return string
   *   Redirect url defined in module settings.
   */
  public function getRedirectUrl() {
    $api_version = $this->configFactory
      ->get('accountkit.settings')
      ->get('redirect_url');
    return $api_version;
  }

  public function getAdditionalFormDetails(){
    $form = [];
    $form['code'] = [
      '#type' => 'hidden',
      '#title' => t('Code'),
      '#description' => t('Hidden code field.'),
      '#attributes' => ['id' => 'code'],

    ];
    $form['csrf'] = [
      '#type' => 'hidden',
      '#title' => t('CSRF'),
      '#description' => ('Hidden CSRF field.'),
      '#attributes' => ['id' => 'csrf'],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];

    $form['#attached'] = [
      'library' => [
        'accountkit/sdk',
        'accountkit/client',
      ],
      'drupalSettings' => [
        'accountkit' => [
          'client' => [
            'app_id' => $this->getAppId(),
            'api_version' => $this->getApiVersion(),
            'redirect_url' => $this->getRedirectUrl(),
          ],
        ],
      ],
    ];

    return $form;
  }

}
