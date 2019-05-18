<?php

namespace Drupal\entity_pilot\Normalizer;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\hal\Normalizer\ContentEntityNormalizer;

/**
 * Normalizer for File entity.
 */
class FileEntityNormalizer extends ContentEntityNormalizer {

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = 'Drupal\file\FileInterface';

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, array $context = []) {
    $data = parent::normalize($entity, $format, $context);
    if (!isset($context['included_fields']) || in_array('data', $context['included_fields'])) {
      // Save base64-encoded file contents to the "data" property.
      $file_data = base64_encode(file_get_contents($entity->getFileUri()));
      $data += [
        'data' => [['value' => $file_data]],
      ];
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = []) {
    // Avoid 'data' being treated as a field.
    $file_data = $data['data'][0]['value'];
    unset($data['data']);
    // Decode and save to file.
    $file_contents = base64_decode($file_data);
    $entity = parent::denormalize($data, $class, $format, $context);
    // @todo Inject file_system component.
    $dirname = drupal_dirname($entity->getFileUri());
    file_prepare_directory($dirname, FILE_CREATE_DIRECTORY);
    if ($uri = file_unmanaged_save_data($file_contents, $entity->getFileUri())) {
      $entity->setFileUri($uri);
    }
    else {
      throw new \RuntimeException(SafeMarkup::format('Failed to write @filename.', ['@filename' => $entity->getFilename()]));
    }
    return $entity;
  }

}
