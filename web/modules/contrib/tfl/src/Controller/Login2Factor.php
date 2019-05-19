<?php

namespace Drupal\tfl\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class Login2Factor.
 *
 * @package Drupal\tfl\Controller\Login2Factor
 */
class Login2Factor extends ControllerBase {


  /**
   * Database connection.
   *
   * @var connection
   */
  protected $connection;

  /**
   * Configuration.
   *
   * @var tflconfigsettings
   */
  protected $tflConfigSettings;

  /**
   * Constructor to create password object.
   */
  public function __construct() {
    $this->connection = TflDependencyInjection::tflDbConnection();
    $this->tflConfigSettings = TflDependencyInjection::tflConfigSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function sendOtp($username) {
    $response = self::getResponse($username);
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function getResponse($username) {
    // Authorized 2factor.in API Key.    
    $api = $this->tflConfigSettings->get('apikey'); 
    // OTP Message type is SMS OR VOICE.
    $otpType = $this->tflConfigSettings->get('type');

    $account = user_load_by_name($username);

    $mobile = !is_null($account->field_mobile_number->value) ? $account->field_mobile_number->value : '';
    $accountChecker = new AccountChecker();
    // Set POST variables.
    $url = 'https://2factor.in/API/V1/' . $api . '/' . $otpType . '/' . $mobile . '/AUTOGEN';
    $response = $accountChecker->get2FactorApiData($url);
    $result = isset($response->Status) ? $response->Status : 'Failed';
    if ($result == 'Error' && !empty( $mobile )) {
      drupal_set_message($response->Details , 'error');
      return;
    }
    if ($result == 'Success' && !empty($mobile)) {
      $otp_session_id = $accountChecker->encryptOrDecrypt('encrypt', $response->Details);
      self::insertUserOtpData($account->uid->value, $otp_session_id);
    }

    return new JsonResponse($response);
  }

  /**
   * Insert session id for OTP.
   *
   * @params
   * $uid  user id
   * $otp_session_id  otp session id
   */
  public function insertUserOtpData($uid, $otp_session_id) {
    $result = $this->connection->merge('users_otp_data')
      ->key(['uid' => $uid])
      ->insertFields([
        'uid' => $uid,
        'otp_session_id' => $otp_session_id,
      ])
      ->updateFields([
        'otp_session_id' => $otp_session_id,
      ])->execute();

    return $result;
  }

}
