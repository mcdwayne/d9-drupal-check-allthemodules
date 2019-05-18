<?php

namespace Drupal\merlinone\Plugin\media\Source;

use Drupal\media\MediaInterface;
use Drupal\media\Plugin\media\Source\File;
use Drupal\merlinone\Traits\MerlinOneMediaSourceTrait;

/**
 * Provides a document media source plugin for MerlinOne.
 *
 * @MediaSource(
 *   id = "merlinone_file",
 *   label = @Translation("MerlinOne Document"),
 *   description = @Translation("MerlinOne document media source"),
 *   allowed_field_types = {"file"},
 * )
 */
class MerlinOneFile extends File implements MerlinOneMediaSourceInterface {

  use MerlinOneMediaSourceTrait;

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes() {
    return $this->getMerlinMetadataAttributes() + parent::getMetadataAttributes();
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(MediaInterface $media, $attribute_name) {
    // Get base File field.
    $value = parent::getMetadata($media, $attribute_name);
    if ($value) {
      return $value;
    }

    return $this->getMerlinMetadata($media, $attribute_name);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityTypeManager() {
    return $this->entityTypeManager;
  }

}
