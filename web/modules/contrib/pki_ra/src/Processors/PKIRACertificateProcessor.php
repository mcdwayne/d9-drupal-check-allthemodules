<?php

namespace Drupal\pki_ra\Processors;

use Drupal\node\Entity\Node;

class PKIRACertificateProcessor extends PKIRAProcessor {

  const NODE_TYPE = 'pki_ra_certificate';

  protected $certificate;

  public function __construct(Node $node) {
    try {
      if ($node->getType() != self::NODE_TYPE) {
        throw new Exception('Wrong certificate type.');
      }
    }
    catch (Exception $e) {
      watchdog_exception('pki_ra', $e, '@class can only be instantiated with the @valid_type type, %invalid_type used.', [
        '@class' => __CLASS__,
        '@valid_type' => self::NODE_TYPE,
        '%invalid_type' => $node->getType(),
      ]);
    }
    $this->certificate = $node;
  }

  public static function createCertificate(Node $registration, $x509) {
    $properties = openssl_x509_parse($x509, FALSE);

    $node = Node::create([
      'type' => self::NODE_TYPE,
      'title' => $properties['serialNumber'],
      'field_certificate_valid_from_t' => ['value' => $properties['validFrom_time_t']],
      'field_certificate_valid_to_t' => ['value' => $properties['validTo_time_t']],
      'field_certificate_country' => ['value' => isset($properties['subject']['countryName']) ?: ''],
      'field_certificate_issuer_name' => ['value' => $properties['issuer']['commonName']],
      'field_certificate_name' => ['value' => $properties['subject']['commonName']],
      'field_certificate_x509' => ['value' => $x509],
    ]);
    $node->field_certificate_registration->target_id = $registration->id();

    return new static($node);
  }

  public function saveCertificate() {
    $this->certificate->save();
    return $this;
  }

}
