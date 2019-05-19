<?php

namespace Drupal\tfl\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class AccountChecker.
 *
 * @package Drupal\tfl\Controller\AccountChecker
 */
class AccountChecker extends ControllerBase {

  /**
   * Salt.
   *
   * @var secretKey
   */
  protected $secretKey;

  /**
   * Salt.
   *
   * @var secretIv
   */
  protected $secretIv;

  /**
   * The password hashing service.
   *
   * @var Drupalservicepassword
   */
  protected $passwordHasher;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

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
    $this->passwordHasher = TflDependencyInjection::tflPasswordService();
    $this->entityManager = TflDependencyInjection::tflEntityManager();
    $this->connection = TflDependencyInjection::tflDbConnection();
    $this->tflConfigSettings = TflDependencyInjection::tflConfigSettings();
    $this->secretKey = $this->tflConfigSettings->get('secret_key');
    $this->secretIv = $this->tflConfigSettings->get('secret_iv');
    
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate($username, $password) {
    $user = FALSE;
    $account = '';
    if (!empty($username) && strlen($password) > 0) {
      $account_search = $this->entityManager->getStorage('user')
        ->loadByProperties(['name' => $username]);
      if ($account = reset($account_search)) {
        if ($this->passwordHasher->check($password, $account->getPassword())) {
          $user = $account;
        }
      }
    }

    return $user;
  }

  /**
   * Get hashed password from user field data table.
   *
   * @params Username
   */
  public function getHashedPassword($username) {
    $query = $this->connection->select('users_field_data', 'ufd');
    $query->fields('ufd', ['pass']);
    $query->condition('ufd.name', $username);
    $result = $query->execute()->fetchField();

    return $result;
  }

  /**
   * Get OTP from user field data table.
   *
   * @params userId
   */
  public function getOtpSessionId($uid) {
    $query = $this->connection->select('users_otp_data', 'uod');
    $query->fields('uod', ['otp_session_id']);
    $query->condition('uod.uid', $uid);
    $result = $query->execute()->fetchField();

    return $result;
  }

  /**
   * Verify otp session id from user field data table.
   *
   * @params
   * $uid  user id
   * $otp_input  user otp
   */
  public function isOtpVerified($uid, $otp) {
    // Authorized 2factor.in API Key.
    $api = $this->tflConfigSettings->get('apikey');
    // OTP Message type is SMS OR VOICE.
    $otpType = $this->tflConfigSettings->get('type');

    $encodedSessionId = $this->getOtpSessionId($uid);
    $sessionId = $this->encryptOrDecrypt('decrypt', $encodedSessionId);
    $url = 'https://2factor.in/API/V1/' . $api . '/' . $otpType . '/VERIFY/' . $sessionId . '/' . $otp;
    $result = self::get2FactorApiData($url);

    return $result;
  }
  
   /**
   * Get balance for OTP.
   *
   * @params
    * @var $type  message type
    * @var $addon_services  boolean 
   */
  public function getBalance($type, $addon_services = FALSE) {
    $config = \Drupal::config( 'tfl.settings' );
    // Authorized 2factor.in API Key.
    $api = $config->get( 'apikey' );
    if ($addon_services) {
      $url = $this->api_url . $api . '/ADDON_SERVICES/BAL/' . $type;
    }else{
      $url = $this->api_url . $api . '/BAL/' . $type;
    }
    $result = self::get2FactorApiData( $url );
    return $result;
  }

  /*
   * Get OTP from user field data table.
   *
   * @params
   * $uid   integer
   * $otp_session_id  string
   *
   * @return bool
   *   : Boolean value 'true' or 'false'.
   */
  public function isOtpSessionIdVerified($uid, $otp_session_id) {
    $status = FALSE;
    $query = $this->connection->select('users_otp_data', 'uod');
    $query->fields('uod', ['uid']);
    $query->condition('uod.uid', $uid);
    $query->condition('uod.otp_session_id', $otp_session_id);
    $result = $query->execute()->fetchField();
    if ((int) $result > 0) {
      $status = TRUE;
    }

    return $status;
  }

  /**
   * Get API data using cURL.
   *
   * @params
   * $url   api url
   */
  public static function get2FactorApiData($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $response = curl_exec($ch);
    curl_close($ch);
    $result = json_decode($response);

    return $result;
  }

  /**
   * Clear user otp data table.
   *
   * @params userId
   */
  public function clearUserOtpData($uid) {
    $query = $this->connection->delete('users_otp_data');
    $query->condition('uid', $uid)->execute();
  }

  /**
   * Simple method to encrypt or decrypt a plain text string.
   *
   * @param string $action
   *   : Can be 'encrypt' or 'decrypt'.
   * @param string $string
   *   : String for 'sessionId'.
   */
  public function encryptOrDecrypt($action, $string) {
    $output = FALSE;
    $encrypt_method = "AES-256-CBC";
    // Hash.
    $key = hash('sha256', $this->secretKey);
    // Iv - encrypt method AES-256-CBC expects 16 bytes.
    $iv = substr(hash('sha256', $this->secretIv), 0, 16);
    if ($action == 'encrypt') {
      $output_encrypt = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
      $output = base64_encode($output_encrypt);
    }
    else {
      if ($action == 'decrypt') {
        $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
      }
    }
    return $output;
  }
  
  
  /*
   * Get mobile number from user__field_mobile_number table.
   * 
   * @params  
   * uid - user id
   */
  public function getShadowMobileNumber($uid) {
    $shadow_number = '';
    $query = $this->connection->select( 'user__field_mobile_number', 'utm' );
    $query->fields( 'utm', ['field_mobile_number_value'] );
    $query->condition( 'utm.entity_id', $uid );
    $result = $query->execute()->fetchField();
    if ($result) {
      $last_2_number = substr($result,-2);
      $phone = substr($result,3, -2);
      $code = substr($result,0, 3); 
      $shadow_number = $code . preg_replace("/[0-9]/", "X", $phone);
      $shadow_number .= $last_2_number;
    }
    return $shadow_number;
  }

}
