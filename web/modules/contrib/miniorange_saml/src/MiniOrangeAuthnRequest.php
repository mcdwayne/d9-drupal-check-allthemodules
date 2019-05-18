<?php
namespace Drupal\miniorange_saml;
use DOMElement;

/**
 * The MiniOrangeAuthnRequest class.
 */
class MiniOrangeAuthnRequest {

  /**
   * The function initiateLogin.
   */
  public function initiateLogin($acs_url, $sso_url, $issuer, $nameid_format, $relay_state) {
    $saml_request = Utilities::createAuthnRequest($acs_url, $issuer, $nameid_format);
	
    if (strpos($sso_url, '?') > 0) {
      $redirect = $sso_url . '&SAMLRequest=' . $saml_request . '&RelayState=' . urlencode($relay_state);
    }
    else {
      $redirect = $sso_url . '?SAMLRequest=' . $saml_request . '&RelayState=' . urlencode($relay_state);	
    }
    // echo($redirect); exit;
    return($redirect);
  }

}
