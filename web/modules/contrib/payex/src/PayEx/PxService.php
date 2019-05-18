<?php
/**
 * @file
 * PxService base class for PayEx API implementation classes.
 */

namespace Drupal\payex\PayEx;

abstract class PxService {
  // These must be set in an implementing class.
  public static $liveURL;
  public static $testURL;
  public $account_number;
  public $key;
  protected $client;

  /**
   * Constructor for setting up the SOAP client.
   */
  public function __construct($account_number, $key, $live = FALSE) {
    $this->account_number = $account_number;
    $this->key = $key;
    
    // Check if we're in live mode. If not, use the testing web service.
    if (!empty($live)) {
      $url = $this::$liveURL;
    }
    else {
      $url = $this::$testURL;
    }

    $this->client = new \SoapClient($url, array(
      'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP,
      'soap_version' => 'SOAP_1_2',
    ));
  }

  /**
   * Flatten the XML response to a simple array.
   */
  public static function flattenResponse($result) {
    // Parse the response with SimpleXML.
    $xml = new \SimpleXMLElement($result);

    // The PayEx XML response is simple enough that we can flatten it
    // by typecasting it to arrays.
    $response = (array) $xml;
    $response['header'] = (array) $response['header'];
    $response['status'] = (array) $response['status'];

    return $response;
  }

  /**
   * Sign the API call parameters with a hash.
   *
   * @param array $params
   *   The parameter
   * @param array $key_order
   *   The order of the keys.
   *
   * @return array
   *   The sign params used in the call.
   */
  public function signParams($params, $key_order) {
    $values = array(); 

    // Default to the client's stored account number.
    $params += array('accountNumber' => $this->account_number);

    foreach ($key_order as $key) {
      // Contrary to the documentation, PayEx requires all params to be
      // set, if only as an empty string.
      if (!isset($params[$key])) {
        $params[$key] = '';
      }
      // Add the keys with values to the values array.
      else {
        $values[] = $params[$key];
      }
    }

    // Finally, add the encryption key to the values array, implode and
    // md5hash it.
    $values[] = $this->key;
    $params['hash'] = md5(implode($values));

    return $params;
  }
}
