<?php

namespace Drupal\entity_normalization_normalizers\Normalizer;

use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Converts the entity into the url.
 */
class EntityUrlNormalizer implements NormalizerInterface {

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    /** @var \Drupal\Core\Entity\EntityInterface $object */

    return $object->toUrl()->toString(TRUE)->getGeneratedUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, $format = NULL) {
    return $data instanceof EntityInterface;
  }

}
