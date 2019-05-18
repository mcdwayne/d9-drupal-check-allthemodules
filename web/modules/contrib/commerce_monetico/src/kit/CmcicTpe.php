<?php

namespace Drupal\commerce_cmcic\kit;

define("CMCIC_CTLHMAC", "V4.0.sha1.php--[CtlHmac%s%s]-%s");
define("CMCIC_CTLHMACSTR", "CtlHmac%s%s");
define("CMCIC_CGI2_RECEIPT", "version=2\ncdr=%s");
define("CMCIC_CGI2_MACOK", "0");
define("CMCIC_CGI2_MACNOTOK", "1\n");
define("CMCIC_CGI2_FIELDS", "%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*");
define("CMCIC_CGI1_FIELDS", "%s*%s*%s%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s");
define("CMCIC_URLPAIEMENT", "paiement.cgi");

/**
 * This class represents all information of the payment kit.
 */
class CmcicTpe {


  // TPE Version (Ex : 3.0).
  public $sVersion;

  // TPE Number (Ex : 1234567).
  public $sNumero;

  // Company code (Ex : companyname).
  public $sCodeSociete;

  // Language (Ex : FR, DE, EN, ..).
  public $sLangue;

  // Return URL OK.
  public $sUrlOK;

  // Return URL KO.
  public $sUrlKO;

  // Payment Server URL (Ex : https://paiement.creditmutuel.fr/paiement.cgi).
  public $sUrlPaiement;

  // The Key.
  protected $sCle;

  /**
   * The constructor of the class.
   *
   * @param string $language
   *   The code language.
   */
  public function __construct($settings, $language = 'FR') {

    // Initialize fields.
    $this->sVersion = $settings['version'];
    $this->sCle = $settings['security_key'];
    $this->sNumero = $settings['tpe'];
    $this->sUrlPaiement = $settings['url_server'] . CMCIC_URLPAIEMENT;

    $this->sCodeSociete = $settings['company'];
    $this->sLangue = $language;

    $this->sUrlOK = $settings['return'];
    $this->sUrlKO = $settings['cancel_return'];

  }

  /**
   * Get the private key.
   *
   * @return string
   *   The private key.
   */
  public function getCle() {
    return $this->sCle;
  }

}
