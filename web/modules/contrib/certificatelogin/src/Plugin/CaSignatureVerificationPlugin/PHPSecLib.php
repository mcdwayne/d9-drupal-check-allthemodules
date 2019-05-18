<?php

namespace Drupal\certificatelogin\Plugin\CaSignatureVerificationPlugin;

use Drupal\certificatelogin\Plugin\CaSignatureVerificationPluginBase;
use phpseclib\File\X509;

/**
 * CA signature verification of client certificates via the PHPSecLib library.
 *
 * @CaSignatureVerificationPlugin(
 *   id = "phpseclib",
 *   label = @Translation("PHPSecLib"),
 * )
 */
class PhpSecLib extends CaSignatureVerificationPluginBase {

  /**
   * {@inheritdoc}
   */
  public function clientCertificateSignedByAuthority($client_certificate, $ca_certificate) {
    $certificate_parser = new X509();
    $certificate_parser->loadCA($ca_certificate);
    $certificate_parser->loadX509($client_certificate);
    return $certificate_parser->validateSignature();
  }

}
