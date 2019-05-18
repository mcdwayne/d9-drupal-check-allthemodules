<?php

namespace Drupal\bibcite_bibtex\Normalizer;

use Drupal\bibcite_entity\Entity\ReferenceInterface;
use Drupal\bibcite_entity\Normalizer\ReferenceNormalizerBase;

/**
 * Normalizes/denormalizes reference entity to BibTeX format.
 */
class BibtexReferenceNormalizer extends ReferenceNormalizerBase {

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
