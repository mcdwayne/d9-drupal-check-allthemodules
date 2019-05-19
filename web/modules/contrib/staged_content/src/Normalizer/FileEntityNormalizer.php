<?php

namespace Drupal\staged_content\Normalizer;

/**
 * Converts the Drupal entity object structure to a HAL array structure.
 */
class FileEntityNormalizer extends ContentEntityNormalizer {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = 'Drupal\file\FileInterface';

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, array $context = []) {
    /** @var \Drupal\file\FileInterface $entity */
    $data = parent::normalize($entity, $format, $context);
    // All the fixtures connected to this entity.
    $data['fixtures'][] = $entity->getFileUri();

    return $data;
  }

}
