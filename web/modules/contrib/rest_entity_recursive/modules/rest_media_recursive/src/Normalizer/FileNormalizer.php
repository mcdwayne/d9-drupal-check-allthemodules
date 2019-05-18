<?php

namespace Drupal\rest_media_recursive\Normalizer;

use Drupal\file\FileInterface;

/**
 * File normalizer for json_recursive format.
 *
 *  @package Drupal\rest_media_recursive\Normalizer
 */
class FileNormalizer extends MediaNormalizer {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = FileInterface::class;

  /**
   * Array of excluded fields.
   *
   * @var array
   */
  protected $excludedFields = [
    'langcode',
    'uid',
    'status',
    'created',
    'changed',
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
