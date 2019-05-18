<?php

namespace Drupal\entity_normalization_normalizers\Normalizer;

use Drupal\taxonomy\TermInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Normalizes the parent of a term into the ID.
 */
class TermParentIdNormalizer implements NormalizerInterface {

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    /** @var \Drupal\taxonomy\TermInterface $object */

    $result = 0;
    if (!empty($object->parents)) {
      $result = (int) $object->parents[0];
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, $format = NULL) {
    return $data instanceof TermInterface;
  }

}
