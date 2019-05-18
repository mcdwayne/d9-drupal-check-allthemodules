<?php

namespace Drupal\certificatelogin\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Certification Authority Signature Verification plugins.
 */
interface CaSignatureVerificationPluginInterface extends PluginInspectionInterface {

  /**
   * Determines if a client certificate was signed by the provided CA.
   *
   * @param x509cert $client_certificate
   *   The client certificate.
   * @param type $ca_certificate
   *   The CA certificate.
   *
   * @return bool
   *   TRUE if the certificate was signed by the CA; FALSE otherwise.
   */
  public function clientCertificateSignedByAuthority($client_certificate, $ca_certificate);

}
