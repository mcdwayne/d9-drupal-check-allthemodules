<?php

namespace Drupal\staged_content\Normalizer;

use Drupal\serialization\Normalizer\FieldNormalizer;

/**
 * Extra normalizer for metatags.
 */
class MetatagFieldNormalizer extends FieldNormalizer {

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = 'Drupal\metatag\Plugin\Field\MetatagEntityFieldItemList';

  /**
   * {@inheritdoc}
   */
  protected $format = ['storage_json'];

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    // @TODO Normalizing of metatag data is currently skipped since it has a
    // lot of hardcoded references that change every time.
    return [];
  }

}
