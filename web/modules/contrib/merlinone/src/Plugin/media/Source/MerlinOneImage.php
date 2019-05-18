<?php

namespace Drupal\merlinone\Plugin\media\Source;

use Drupal\media\MediaInterface;
use Drupal\media\Plugin\media\Source\Image;
use Drupal\merlinone\Traits\MerlinOneMediaSourceTrait;

/**
 * Provides a media source plugin for MerlinOne.
 *
 * @MediaSource(
 *   id = "merlinone_image",
 *   label = @Translation("MerlinOne Image"),
 *   description = @Translation("MerlinOne image media source"),
 *   allowed_field_types = {"image"}
 * )
 */
class MerlinOneImage extends Image implements MerlinOneMediaSourceInterface {

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
    // Get base Image field.
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
