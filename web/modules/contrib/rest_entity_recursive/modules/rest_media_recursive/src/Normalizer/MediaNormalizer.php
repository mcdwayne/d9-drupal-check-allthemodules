<?php

namespace Drupal\rest_media_recursive\Normalizer;

use Drupal\media\MediaInterface;
use Drupal\rest_entity_recursive\Normalizer\ContentEntityNormalizer;

/**
 * Media normalizer for json_recursive format.
 *
 * @package Drupal\rest_media_recursive\Normalizer
 */
class MediaNormalizer extends ContentEntityNormalizer {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = MediaInterface::class;

  /**
   * Array of excluded fields.
   *
   * @var array
   */
  protected $excludedFields = [
    'vid',
    'langcode',
    'bundle',
    'revision_created',
    'revision_user',
    'revision_log_message',
    'status',
    'uid',
    'created',
    'changed',
    'default_langcode',
    'revision_translation_affected',
    'metatag',
    'metatag_normalized',
    'path',
    'thumbnail'
  ];

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, array $context = []) {
    // Add the current entity as a cacheable dependency to make Drupal flush
    // the cache when the media entity gets updated.
    $this->addCacheableDependency($context, $entity);

    // Ask REST Entity Recursive to exclude certain fields.
    $context['settings'][$entity->getEntityTypeId()]['exclude_fields'] = $this->excludedFields;
    $normalized_values = parent::normalize($entity, $format, $context);
    return $normalized_values;
  }

}
