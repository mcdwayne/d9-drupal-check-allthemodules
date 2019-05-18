<?php

/**
 * @file
 * Extends the SoapClient class to handle SOAP requests.
 */

namespace Drupal\media_entity_panopto;

/**
 * SOAP class implementation for Panopto.
 */
class PanoptoSessionManagementSoapClient extends \SoapClient {
  /**
   * Namespace used for any root level objects.
   *
   * @var string
   */
  const ROOT_LEVEL_NAMESPACE = "http://tempuri.org/";

  /**
   * Namespace used for object members.
   *
   * @var string
   */
  const OBJECT_MEMBER_NAMESPACE = "http://schemas.datacontract.org/2004/07/Panopto.Server.Services.PublicAPI.V40";

  /**
   * Namespace used for sessionIds.
   *
   * @var string
   */
  const ARRAY_MEMBER_NAMESPACE = "http://schemas.microsoft.com/2003/10/Serialization/Arrays";

  /**
   * Namespace used for ArraysOfGuid.
   *
   * @var string
   */
  const SER_MEMBER_NAMESPACE = "http://schemas.microsoft.com/2003/10/Serialization/";

  /**
   * Username of calling user.
   *
   * @var string
   */
  public $ApiUserKey;

  /**
   * Auth code generated for calling user.
   *
   * @var string
   */
  public $ApiUserAuthCode;

  /**
   * Name of Panopto server being called.
   *
   * @var string
   */
  public $Servername;

  /**
   * Password needed if provider does not have a bounce page.
   *
   * @var string
   */
  public $Password;

  /**
   * Store the current action.
   *
   * @var string
   */
  public $currentaction;

  /**
   * Constructor function.
   */
  public function __construct($servername, $apiuseruserkey, $apiuserauthcode, $password) {
    $this->ApiUserKey = $apiuseruserkey;
    $this->ApiUserAuthCode = $apiuserauthcode;
    $this->Servername = $servername;
    $this->Password = $password;

    // Instantiate SoapClient in WSDL mode.
    // Set call timeout to 5 minutes.
    parent::__construct("https://" . $servername . "/Panopto/PublicAPI/4.6/SessionManagement.svc?wsdl");
  }

  /**
   * Helper method for making a call to the Panopto API.
   */
  public function callWebMethod($methodname, $namedparams = array(), $auth = TRUE) {
    $params = array();
    // Include API user and auth code params unless $auth is set to false.
    if ($auth) {
      // Create SoapVars for AuthenticationInfo object members.
      $authinfo = new \stdClass();
      $authinfo->AuthCode = new \SoapVar(
        $this->ApiUserAuthCode,
        XSD_STRING,
        NULL,
        NULL,
        NULL,
        self::OBJECT_MEMBER_NAMESPACE
      );

      // Add the password parameter if a password is provided.
      if (!empty($this->Password)) {
        $authinfo->Password = new \SoapVar($this->Password, XSD_STRING, NULL, NULL, NULL, self::OBJECT_MEMBER_NAMESPACE);
      }
      $authinfo->AuthCode = new \SoapVar($this->ApiUserAuthCode, XSD_STRING, NULL, NULL, NULL, self::OBJECT_MEMBER_NAMESPACE);
      $authinfo->UserKey = new \SoapVar($this->ApiUserKey, XSD_STRING, NULL, NULL, NULL, self::OBJECT_MEMBER_NAMESPACE);

      // Create a container for storing all of the soap vars.
      $obj = array();
      // Add auth info to $obj container.
      $obj['auth'] = new \SoapVar($authinfo, SOAP_ENC_OBJECT, NULL, NULL, NULL, self::ROOT_LEVEL_NAMESPACE);
      // Add the soapvars from namedparams to the container.
      foreach ($namedparams as $key => $value) {
        $obj[$key] = $value;
      }
      // Create a soap param using the obj container.
      $param = new \SoapParam(new \SoapVar($obj, SOAP_ENC_OBJECT), 'data');
      // Add the created soap param to an array to be passed to __soapCall.
      $params = array($param);
    }
    // Update current action with the method being called.
    $this->currentaction = "http://tempuri.org/ISessionManagement/$methodname";

    // Make the SOAP call via SoapClient::__soapCall.
    return parent::__soapCall($methodname, $params);
  }

  /**
   * Sample function for calling an API method.
   */
  public function getSessionList($request, $searchQuery) {
    $requestvar = new \SoapVar($request, SOAP_ENC_OBJECT, NULL, NULL, NULL, self::ROOT_LEVEL_NAMESPACE);
    $searchQueryVar = new \SoapVar($searchQuery, XSD_STRING, NULL, NULL, NULL, self::ROOT_LEVEL_NAMESPACE);
    return self::callWebMethod("GetSessionsList", array("request" => $requestvar, "searchQuery" => $searchQueryVar));
  }

  /**
   * Sample function for calling an API method.
   */
  public function getSessionsById($sessionId) {
    $searchQueryVar = new \SoapVar($sessionId, SOAP_ENC_OBJECT, NULL, NULL, NULL, self::ROOT_LEVEL_NAMESPACE);
    return self::callWebMethod("GetSessionsById", array("sessionIds" => $searchQueryVar));
  }

  /**
   * Override SOAP action to work around bug in older PHP SOAP versions.
   */
  public function __doRequest($request, $location, $action, $version, $oneway = NULL) {
    return parent::__doRequest($request, $location, $this->currentaction, $version);
  }

}
