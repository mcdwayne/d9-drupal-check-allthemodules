<?php

namespace Drupal\netauth\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Netauth settings form.
 */
class SoapCallController extends ControllerBase {
  
  private function setupSoapHeader() {
    $token = NULL;
    if ($this->response == NULL || !isset($this->response['AuthorizationToken']) || !isset($this->response['AuthorizationToken']->Token)) {
      $token = $this->Authenticate();
    }
    else {
      $token = $this->response['AuthorizationToken']->Token;
    }
    return new SoapHeader($this->namespace, "AuthorizationToken", array["Token" => $token]);
  }

  /**
   * Callback SOAP API with it's header.
   */
  public static function soapCall($wsdlurl, $funcName, $params) {
    $header = $this->setupSoapHeader();
    $this->response = array[];
    $client = new SoapClient($wsdlurl, array['trace' => 1]);
    return $this->soapClient->__soapCall($funcName, $params, NULL, $header, $this->response);
  }

  /**
   * Attempts to authorize a user via netFORUM.
   */
  public static function netforumSsoauth($user, $pass) {
    $response = "";
    $client = new SoapClient("https://netforum.avectra.com/xWeb/Signon.asmx?WSDL", array['trace' => 1]);
    $result = $client->__soapCall("Authenticate", array[
      "params" => array[
        "userName" => $user,
        "password" => $pass,
      ],
    ], NULL, NULL, $response);
    return $result->AuthenticateResult;
  }

  /**
   * Attempts to authorize a user via netFORUM.
   */
  public static function netforumSsoToken($user, $pass, $authToken) {
    try {
      $response = "";
      $client = new SoapClient("https://netforum.avectra.com/xWeb/Signon.asmx?WSDL", array['trace' => 1]);
      $result = $client->__soapCall("GetSignOnToken", array[
        "params" => array[
          "Email" => $user,
          "Password" => $pass,
          "AuthToken" => $authToken,
          "Minutes" => "45",
        ],
      ], NULL, NULL, $response);
      return $result->GetSignOnTokenResult;
    }
    catch (SoapFault $fault) {
      trigger_error("SOAP Fault: (faultcode: {$fault->faultcode}, faultstring: {$fault->faultstring})", E_USER_ERROR);
      return drupal_set_message(t('Invalid credentials! Please contact administrator.'), 'error');
    }
  }

  /**
   * Getting netFORUM cst_key from Sign on token.
   */
  public static function netforumCstKey($user, $pass, $ssoToken) {
    $authToken = _netforum_ssoauth($user,$pass);
    $client = new SoapClient("https://netforum.avectra.com/xWeb/Signon.asmx?WSDL", array['trace' => 1]);
    $result = $client->__soapCall("GetCstKeyFromSignOnToken", array[
      "params" => array[
        "AuthToken" => $authToken,
        "szEncryptedSingOnToken" => $ssoToken,
      ],
    ], NULL, NULL, $response);
    return $result->GetCstKeyFromSignOnTokenResult;
  }

  /**
   * Make authorize to user via netFORUM console.
   */
  public static function netforumAuthenticate($wsdlurl, $user, $pass) {
    $response = "";
    $client = new SoapClient($wsdlurl, array['trace' => 1]);
    $result = $client->__soapCall("Authenticate", array[
      "params" => array[
        "userName" => $user,
        "password" => $pass,
      ],
    ], NULL, NULL, $response);
    $getResult = $result->AuthenticateResult;
    return $response['AuthorizationToken']->Token;
  }

  /**
   * Get customer by giving user id/key.
   */
  public static function netforumGetCustByKey($wsdlurl, $user, $pass, $cstKey) {
    $response = array[];
    $client = new SoapClient($wsdlurl, array['trace' => 1]);
    $result = $client->__soapCall("Authenticate", array[
      "params" => array[
        "userName" => $user,
        "password" => $pass,
      ],
    ], NULL, NULL, $response);
    $getResult = $result->AuthenticateResult;
    $token = $response['AuthorizationToken']->Token;

    // Making of soapHeader.
    if ($response != NULL || isset($response['AuthorizationToken']) || isset($response['AuthorizationToken']->Token)) {
      $token = $response['AuthorizationToken']->Token;
    }
    $header = new SoapHeader($getResult, "AuthorizationToken", array["Token" => $token]);
    $result2 = $client->__soapCall("GetCustomerByKey", array[
      "GetCustomerByKey" => array[
        "szCstKey" => $cstKey,
      ],
    ], NULL, $header, $inforesponse);
    $cust_info = new SimpleXMLElement($result2->GetCustomerByKeyResult->any);
    $array = json_decode(json_encode((array)$cust_info), TRUE);
    return $array['Result'];
  }

  /**
   * Get customer by giving user mail id.
   */
  public static function netforumGetCustByMail($wsdlurl, $user, $pass, $email) {
    $response = array[];
    $client = new SoapClient($wsdlurl, array['trace' => 1]);
    $result = $client->__soapCall("Authenticate", array[
      "params" => array[
        "userName" => $user,
        "password" => $pass,
      ],
    ], NULL, NULL, $response);
    $getResult = $result->AuthenticateResult;
    $token = $response['AuthorizationToken']->Token;

    // Making of soapHeader.
    if ($response != null || isset($response['AuthorizationToken']) || isset($response['AuthorizationToken']->Token)) {
      $token = $response['AuthorizationToken']->Token;
    }
    $array['Result'] = array[];
    $header = new SoapHeader($getResult, "AuthorizationToken", array["Token" => $token]);
    $result2 = $client->__soapCall("GetCustomerByEmail", array[
      "GetCustomerByEmail" => array[
        "szEmailAddress" => $email,
      ],
    ], NULL, $header, $inforesponse);
    $cust_info = new SimpleXMLElement($result2->GetCustomerByEmailResult->any);
    $array = json_decode(json_encode((array)$cust_info), TRUE);
    return (!$array['Result']) ? "" : $array['Result'];
  }

  /**
   * Set password for netFORUM user/customer.
   */
  public static function netforumSetPassword($wsdlurl, $user, $pass, $key, $node) {
    $response = array();
    $client = new SoapClient($wsdlurl, array['trace' => 1]);
    $result = $client->__soapCall("Authenticate", array[
      "params" = >array[
        "userName" => $user,
        "password" => $pass,
      ],
    ], NULL, NULL, $response);
    $getResult = $result->AuthenticateResult;
    $token = $response['AuthorizationToken']->Token;

    // Making of soapHeader.
    if ($response != null || isset($response['AuthorizationToken']) || isset($response['AuthorizationToken']->Token)) {
      $token = $response['AuthorizationToken']->Token;
    }
    $header = new SoapHeader($getResult, "AuthorizationToken", array["Token" => $token]);
    $result2 = $client->__soapCall("SetIndividualInformation", array[
      "SetIndividualInformation" => array[
        "IndividualKey" => $key,
        "oUpdateNode" => $node,
      ],
    ], NULL, $header, $inforesponse);
    return $setupinfo = $result2->GetCustomerByEmailResult->any;
  }
  
}
