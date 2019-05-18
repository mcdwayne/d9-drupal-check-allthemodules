<?php

namespace Drupal\entity_normalization_normalizers\Normalizer;

use Drupal\node\NodeTypeInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Converts the node type into the value.
 */
class NodeTypeNormalizer implements NormalizerInterface {

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    /** @var \Drupal\node\NodeTypeInterface $object */
    return $object->id();
  }

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, $format = NULL) {
    return $data instanceof NodeTypeInterface;
  }

}
