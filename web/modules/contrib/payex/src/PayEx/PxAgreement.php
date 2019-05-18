<?php
/**
 * @file
 * PxAgreement class for PayEx Autopay agreements.
 *
 * This is the raw integration of Soap calls and for internal use only.
 */

namespace Drupal\payex\PayEx;

class PxAgreement extends PxService {
  public static $liveURL = 'https://external.payex.com/pxagreement/pxagreement.asmx?WSDL';
  public static $testURL = 'https://test-external.payex.com/pxagreement/pxagreement.asmx?WSDL';

  /**
   * Implementation of PxAgreement.AutoPay.
   *
   * @param array $params
   *   The params used for the autoPay call
   *
   * @return array
   *   The result of the request outputted as a simple array
   *
   * @throws PayExAPIException
   *   If api call to payex gave an error.
   */
  function autoPay($params = array()) {
    // Correct order of the params when hashing.
    $key_order = array('accountNumber', 'agreementRef', 'price', 'productNumber', 'description', 'orderId', 'purchaseOperation', 'currency');

    // Make the SOAP call.
    $call = $this->client->AutoPay3($this->signParams($params, $key_order));
    $response = self::flattenResponse($call->AutoPay3Result);

    if ($response['status']['errorCode'] != 'OK') {
      throw new PayExAPIException($response['status']['description']);
    }

    return $response;
  }

  /**
   * Implementation of PxAgreement.Check.
   *
   * @param array $params
   *   The params used for the check call.
   *
   * @return array
   *   The result of the request outputted as a simple array.
   *
   * @throws PayExAPIException
   *   If api call to payex gave an error.
   */
  function check($params = array()) {
    // Correct order of the params when hashing.
    $key_order = array('accountNumber', 'agreementRef');

    // Make the SOAP call.
    $call = $this->client->Check($this->signParams($params, $key_order));
    $response = self::flattenResponse($call->CheckResult);

    if ($response['status']['errorCode'] != 'OK') {
      throw new PayExAPIException($response['status']['description']);
    }
    return $response;
  }

  /**
   * Implementation of PxAgreement.CreateAgreement.
   *
   * @param array $params
   *   The params used for the createAgreement call.
   *
   * @return string|FALSE
   *   The agreement ref or FALSE if no agreement was created.
   *
   * @throws PayExAPIException
   *   If api call to payex gave an error.
   */
  function createAgreement($params = array()) {
    // Correct order of the params when hashing.
    $key_order = array('accountNumber', 'merchantRef', 'description', 'purchaseOperation', 'maxAmount', 'notifyUrl', 'startDate', 'stopDate');

    // Make the SOAP call.
    $call = $this->client->CreateAgreement3($this->signParams($params, $key_order));
    $response = self::flattenResponse($call->CreateAgreement3Result);

    if ($response['status']['errorCode'] != 'OK') {
      throw new PayExAPIException($response['status']['description']);
    }
    elseif (!empty($response['agreementRef'])) {
      return $response['agreementRef'];
    }

    // In case of failure, return FALSE.
    return FALSE;
  }

  /**
   * Implementation of PxAgreement.DeleteAgreement.
   *
   * @param array $params
   *   The params used for the deleteAgreement call.
   *
   * @return array
   *   The result of the request outputted as a simple array.
   *
   * @throws PayExAPIException
   *   If api call to payex gave an error.
   */
  function deleteAgreement($params = array()) {
    // Correct order of the params when hashing.
    $key_order = array('accountNumber', 'agreementRef');

    // Make the SOAP call.
    $call = $this->client->DeleteAgreement($this->signParams($params, $key_order));
    $response = self::flattenResponse($call->DeleteAgreementResult);

    if ($response['status']['errorCode'] != 'OK') {
      throw new PayExAPIException($response['status']['description']);
    }

    return $response;
  }
}
