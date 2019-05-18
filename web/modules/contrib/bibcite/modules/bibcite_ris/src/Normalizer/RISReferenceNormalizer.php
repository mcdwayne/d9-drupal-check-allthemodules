<?php

namespace Drupal\bibcite_ris\Normalizer;

use Drupal\bibcite_entity\Entity\ReferenceInterface;
use Drupal\bibcite_entity\Normalizer\ReferenceNormalizerBase;

/**
 * Normalizes/denormalizes reference entity to RIS format.
 */
class RISReferenceNormalizer extends ReferenceNormalizerBase {

  /**
   * {@inheritdoc}
   */
  protected function extractFields(ReferenceInterface $reference, $format) {
    $attributes = parent::extractFields($reference, $format);
    $isbn = $this->extractScalar($reference->get('bibcite_isbn'));
    $issn = $this->extractScalar($reference->get('bibcite_issn'));
    if ($isbn || $issn) {
      $attributes['SN'] = trim($isbn . '/' . $issn, '/');
    }
    return $attributes;
  }

}
