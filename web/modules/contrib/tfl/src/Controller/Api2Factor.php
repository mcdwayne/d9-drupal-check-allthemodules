<?php

namespace Drupal\tfl\Controller;

use Drupal\Core\Controller\ControllerBase;


/**
 * Class Api2Factor.
 *
 * @package Drupal\tfl\Controller\Api2Factor
 */
class Api2Factor extends ControllerBase {

  
  /**
   * 2factor API url.
   *
   * @var
   */
  protected $api_url = 'https://2factor.in/API/V1/';
  
  /**
   * The entity manager.
   *
   * @var \Drupal\tfl\Controller\tflEntityManager
   */
  protected $entityManager;

  /**
   * Configuration.
   *
   * @var \Drupal\tfl\Controller\tflConfigSettings
   */
  protected $tflConfigSettings;
  
  
  
  /*
   * Constructor to create password object.
   */
  public function __construct() {
    $this->entityManager = TflDependencyInjection::tflEntityManager();
    $this->tflConfigSettings = TflDependencyInjection::tflConfigSettings();
  }

  /*
   * Get API data using cURL.
   * 
   * @params  
   * $url   api url
   */
  public static function get2FactorApiData($url) {
    $ch = curl_init();
    curl_setopt( $ch, CURLOPT_URL, $url );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
    $response = curl_exec( $ch );
    curl_close( $ch );
    $result = json_decode( $response );

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
    $api = $this->tflConfigSettings->get( 'apikey' );
    // OTP Message type is SMS OR VOICE.
    $otpType = $this->tflConfigSettings->get( 'type' );

    $encodedSessionId = $this->getOtpSessionId( $uid );
    $sessionId = $this->encryptOrDecrypt( 'decrypt', $encodedSessionId );
    $url = $this->api_url . $api . '/' . $otpType . '/VERIFY/' . $sessionId . '/' . $otp;
    $result = self::get2FactorApiData( $url );

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
    // Authorized 2factor.in API Key.
    $api = $this->tflConfigSettings->get( 'apikey' );
    if ($addon_services) {
      $url = $this->api_url . $api . '/ADDON_SERVICES/BAL/' . $type;
    }else{
      $url = $this->api_url . $api . '/BAL/' . $type;
    }
    $result = self::get2FactorApiData( $url );
    return $result;
  }
  
  
  /**
   * Get balance for OTP.
   *
   * @params
   * @var $type  message type
   * @var $addon_services  boolean 
   */
  public function isValidApiKey($api, $type, $addon_services = FALSE) {
    // Authorized 2factor.in API Key.
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
   * @return  boolean
   */
  public function isOtpSessionIdVerified($uid, $otp_session_id) {
    $status = FALSE;
    $query = $this->connection->select( 'users_otp_data', 'uod' );
    $query->fields( 'uod', ['uid'] );
    $query->condition( 'uod.uid', $uid );
    $query->condition( 'uod.otp_session_id', $otp_session_id );
    $result = $query->execute()->fetchField();
    if ((int) $result > 0) {
      $status = TRUE;
    }

    return $status;
  }  

  
  /**
   * ADD or REMOVE phone number in Block list from https://2factor.in.
   *
   * @params
   * @var $type  message type
   * @var $action   
   */
  public function getStatusBlockPhoneNumber($mobile, $type, $action = '') {
    // Authorized 2factor.in API Key.
    $api = $this->tflConfigSettings->get( 'apikey' );
    $url = $this->api_url . $api . '/BLOCK/' . $mobile . '/' . $type . '/' . $action;
    $result = self::get2FactorApiData( $url );
    return $result;
  }

  

}      
