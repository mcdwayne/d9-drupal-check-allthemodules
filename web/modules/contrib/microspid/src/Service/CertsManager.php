<?php

namespace Drupal\microspid\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Site\Settings;

/**
 * Service to interact with the Spid Certs.
 */
class CertsManager {
  protected $config;
  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory->get('microspid.settings');
  }
  /**
   * @return bool
   *   private key path exsistence
   */
  public function certExists() {
    return file_exists($this->getPrivateKeyPath());
  }
  /**
   * @return string
   *   private key path 
   */
  public function getPrivateKeyPath() {
    $path = $this->config->get('privatepath');
    if (empty($path)) {
      $path = \Drupal::service('file_system')->realpath('private://microspid') . '/cert';
      //return $path . '/spid-sp.pem';
    }
    return $path . '/spid-sp.pem';    
  }

  /**
   * @param string $cert
   *   the certificate
   * @param bool $heads
   *   add headers?
   * @return string
   *   formatted certificate
   */
  public function formatCert($cert, $heads = FALSE) {
    $x509cert = str_replace(array("\x0D", "\r", "\n"), "", $cert);
    if (!empty($x509cert)) {
      $x509cert = str_replace('-----BEGIN CERTIFICATE-----', "", $x509cert);
      $x509cert = str_replace('-----END CERTIFICATE-----', "", $x509cert);
      $x509cert = str_replace(' ', '', $x509cert);

      if ($heads) {
        $x509cert = "-----BEGIN CERTIFICATE-----\n" . chunk_split($x509cert, 64, "\n") . "-----END CERTIFICATE-----\n";
      }

    }
    return $x509cert;
  }

  /**
   * @param string $file
   *   certificate file path
   * @return mixed
   *   formatted certificate | FALSE
   */
  public function getCert($file) {
    $key = file_get_contents($file);
    return $key === FALSE ? FALSE : $this->formatCert($key);
  }
  
  /**
   * @param array $dn
   *   params for certificate
   * 
   */
  public function makeCerts($dn) {
    $numberofdays = 3652 * 2;
    $privkey = openssl_pkey_new(array(
      'private_key_bits' => 2048,
      'private_key_type' => OPENSSL_KEYTYPE_RSA,
      'x509_extensions' => 'v3_ca',
      'digest_alg' => 'sha256',
    ));
    $csr = openssl_csr_new($dn, $privkey);
    $myserial = hexdec(uniqid());

    // Do cert.
    $configArgs = array("digest_alg" => "sha256");
    $sscert = openssl_csr_sign($csr, NULL, $privkey, $numberofdays, $configArgs, (int) $myserial);
    openssl_x509_export($sscert, $publickey);
    openssl_pkey_export($privkey, $privatekey);

    $path = $this->config->get('privatepath');
    if (empty($path)) {
      $path = \Drupal::service('file_system')->realpath('private://microspid') . '/cert';
    }
    $pathname = $path . '/spid-sp';
    file_put_contents($pathname . '.pem', $privatekey);
    file_put_contents($pathname . '.crt', $publickey);    
  }
}
