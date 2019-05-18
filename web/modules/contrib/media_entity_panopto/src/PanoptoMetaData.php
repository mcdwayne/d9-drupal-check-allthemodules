<?php

namespace Drupal\media_entity_panopto;

use Drupal\media_entity_panopto\PanoptoSessionManagementSoapClient;

class PanoptoMetaData {
  /**
   * Panopto Video ID.
   *
   * @var string
   */
  public $video_id;
  
  /**
   * Panopto client domain.
   *
   * @var string
   */
  public $client;

  /**
   * Constructor function.
   */
  public function __construct($video_id, $client) {
    $this->video_id = $video_id;
    $this->client = $client;
  }

  /**
   * Function to get meta data of video from Panopto server.
   */
  public function media_panopto_get_meta_data() {
    // The username of the calling panopto user.
    $user_key = '';

    // The name of the panopto server (i.e. demo.hosted.panopto.com).
    $server_name = $this->client;

    // The application key from provider on the Panopto provider's page.
    $application_key = '';

    // Password of the calling user on Panopto server.
    $password = NULL;

    // Generate an auth code.
    $auth_code = $this->media_panopto_generate_auth_code($user_key, $server_name, $application_key);

    // Create a SOAP client for the desired Panopto API class.
    $session_management_client = new PanoptoSessionManagementSoapClient($server_name, $user_key, $auth_code, $password);

    // Set https endpoint in case wsdl specifies http.
    $session_management_client->__setLocation("https://" . $server_name . "/Panopto/PublicAPI/4.6/SessionManagement.svc");

    $session_id_obj = new \SoapVar($this->video_id, XSD_STRING, NULL, NULL, NULL, PanoptoSessionManagementSoapClient::ARRAY_MEMBER_NAMESPACE);
    $array_of_guid = new \stdClass();
    $array_of_guid->guid = $session_id_obj;
    $array_of_guid_obj = new \SoapVar($array_of_guid, SOAP_ENC_OBJECT, NULL, NULL, NULL, PanoptoSessionManagementSoapClient::SER_MEMBER_NAMESPACE);

    // Call api and get respponse.
    $response_access_details = $session_management_client->getSessionsById($array_of_guid_obj);

    if (!empty($response_access_details->GetSessionsByIdResult->Session)) {
      return $response_access_details->GetSessionsByIdResult->Session;
    }
    else {
      return FALSE;
    }
  }
  
  /**
   * Function to create an API Authentication code.
   */
  protected function media_panopto_generate_auth_code($userkey, $servername, $applicationkey) {
    $payload = $userkey . "@" . $servername;
    $signedpayload = $payload . "|" . $applicationkey;
    $authcode = strtoupper(sha1($signedpayload));
    return $authcode;
  }
}
