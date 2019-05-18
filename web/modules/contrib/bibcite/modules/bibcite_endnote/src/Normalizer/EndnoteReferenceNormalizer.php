<?php

namespace Drupal\bibcite_endnote\Normalizer;

use Drupal\bibcite_entity\Entity\ReferenceInterface;
use Drupal\bibcite_entity\Normalizer\ReferenceNormalizerBase;

/**
 * Normalizes/denormalizes reference entity to Endnote format.
 */
class EndnoteReferenceNormalizer extends ReferenceNormalizerBase {

  /**
   * {@inheritdoc}
   */
  protected function extractFields(ReferenceInterface $reference, $format) {
    $attributes = parent::extractFields($reference, $format);
    $attributes['title'] = $this->extractScalar($reference->get('title'));
    return $attributes;
  }

}
