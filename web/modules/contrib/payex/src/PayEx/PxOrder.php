<?php
/**
 * @file
 * PxOrder class for paying orders with PayEx.
 *
 * This is the raw integration of Soap calls and for internal use only.
 */

namespace Drupal\payex\PayEx;

class PxOrder extends PxService {
  public static $liveURL = 'https://external.payex.com/pxorder/pxorder.asmx?WSDL';
  public static $testURL = 'https://test-external.payex.com/pxorder/pxorder.asmx?WSDL';

  /**
   * Implementation of PXOrder.Initialize.
   *
   * @param array $params
   *   The params used for the initialize call
   *
   * @return array
   *   The result of the request outputted as a simple array
   *
   * @throws PayExAPIException
   *   If api call to payex gave an error.
   */
  function initialize($params = array()) {
    // Correct order of the params when hashing.
    $key_order = array('accountNumber', 'purchaseOperation', 'price', 'priceArgList', 'currency', 'vat', 'orderID', 'productNumber', 'description', 'clientIPAddress', 'clientIdentifier', 'additionalValues', 'externalID', 'returnUrl', 'view', 'agreementRef', 'cancelUrl', 'clientLanguage');

    // Make the SOAP call.
    $call = $this->client->Initialize8($this->signParams($params, $key_order));
    $response = self::flattenResponse($call->Initialize8Result);

    if ($response['status']['errorCode'] != 'OK') {
      throw new PayExAPIException($response['status']['description']);
    }

    return $response;
  }

  /**
   * Implementation of PXOrder.Complete.
   */
  function complete($params = array()) {
    $key_order = array('accountNumber', 'orderRef');

    // Make the SOAP call.
    $call = $this->client->Complete($this->signParams($params, $key_order));
    $response = self::flattenResponse($call->CompleteResult);

    if ($response['status']['errorCode'] != 'OK') {
      throw new PayExAPIException($response['status']['description']);
    }

    return $response;
  }
}
