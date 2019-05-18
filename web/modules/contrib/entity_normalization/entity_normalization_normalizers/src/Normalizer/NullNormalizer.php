<?php

namespace Drupal\entity_normalization_normalizers\Normalizer;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Normalizer for anything to NULL.
 */
class NullNormalizer implements NormalizerInterface {

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, $format = NULL) {
    return TRUE;
  }

}
