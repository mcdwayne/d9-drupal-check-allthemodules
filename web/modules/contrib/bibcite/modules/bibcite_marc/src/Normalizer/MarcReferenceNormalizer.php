<?php

namespace Drupal\bibcite_marc\Normalizer;

use Drupal\bibcite_entity\Entity\ReferenceInterface;
use Drupal\bibcite_entity\Normalizer\ReferenceNormalizerBase;

/**
 * Normalizes/denormalizes reference entity to Marc format.
 */
class MarcReferenceNormalizer extends ReferenceNormalizerBase {

  /**
   * {@inheritdoc}
   */
  protected function extractFields(ReferenceInterface $reference, $format) {
    $attributes = parent::extractFields($reference, $format);
    $attributes['title'] = $this->extractScalar($reference->get('title'));
    $attributes['reference'] = $reference->id();
    return $attributes;
  }

}
