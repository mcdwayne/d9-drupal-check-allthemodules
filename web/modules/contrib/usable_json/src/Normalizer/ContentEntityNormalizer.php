<?php

namespace Drupal\usable_json\Normalizer;

use Drupal\serialization\Normalizer\EntityNormalizer as DrupalEntityNormalizer;

/**
 * Adds the file URI to embedded file entities.
 */
class ContentEntityNormalizer extends DrupalEntityNormalizer {

  /**
   * The formats that the Normalizer can handle.
   *
   * @var array
   */
  protected $format = ['usable_json'];

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = ['Drupal\Core\Entity\ContentEntityInterface'];

  /**
   * {@inheritdoc}
   */
  public function normalize($field_item, $format = NULL, array $context = []) {
    /* @var \Drupal\node\Entity\Node $field_item */
    $values = parent::normalize($field_item, $format, $context);
    return $values;
  }

}
