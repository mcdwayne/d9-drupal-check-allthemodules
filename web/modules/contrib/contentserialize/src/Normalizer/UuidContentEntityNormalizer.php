<?php

namespace Drupal\contentserialize\Normalizer;

use Drupal\serialization\Normalizer\ContentEntityNormalizer;

/**
 * Normalizes/denormalizes content entities replacing serial IDs with UUids.
 */
class UuidContentEntityNormalizer extends ContentEntityNormalizer {

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = array()) {
    $attributes = parent::normalize($object, $format, $context);
    $keys = $object->getEntityType()->getKeys();
    foreach (['id', 'revision'] as $key_name) {
      unset($attributes[$keys[$key_name]]);
    }

    // Not all entity types that support FieldableEntityInterface have bundle
    // keys, eg. user and file.
    if (!empty($keys['bundle'])) {
      $attributes[$keys['bundle']] = $object->bundle();
    }

    return $attributes;
  }

}
