<?php

namespace Drupal\saml_sp_drupal_login\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\saml_sp\Entity\Idp;

/**
 * Provides route responses for the SAML SP module.
 */
class SamlSPDrupalLoginController extends ControllerBase {

  /**
   * Initiate a SAML login for the given IdP.
   */
  public function initiate(Idp $idp) {
    // Start the authentication process; invoke
    // saml_sp_drupal_login__saml_authenticate() when done.
    $return = saml_sp_start($idp, 'saml_sp_drupal_login__saml_authenticate');
    if (!empty($return)) {
      // Something was returned, echo it to the screen.
      return $return;
    }
  }

}
