<?php

/**
 * @file
 * Contains \Drupal\collect_client\Normalizer\CollectItemNormalizer.
 */

namespace Drupal\collect_client\Normalizer;

use Drupal\serialization\Normalizer\NormalizerBase;

/**
 * Converts the Drupal field definition structure to array structure.
 */
class CollectItemNormalizer extends NormalizerBase {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = 'Drupal\collect_client\CollectItem';

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = array()) {
    /* @var \Drupal\collect_client\CollectItem $object */
    return array(
      'origin_uri' => $object->uri,
      'schema_uri' => $object->schema_uri,
      'data' => $object->data,
      'type' => $object->type,
      'date' => $object->date,
    );
  }
}
