<?php

namespace Drupal\media_entity_threejs\Plugin\media\Source;

use Drupal\media\MediaTypeInterface;
use Drupal\media\Plugin\media\Source\File;

/**
 * Provides media type plugin for threejs.
 *
 * @MediaSource(
 *   id = "threejs",
 *   label = @Translation("threejs"),
 *   description = @Translation("Provides business logic and metadata for threejs Files."),
 *   allowed_field_types = {"file"},
 *   default_thumbnail_filename = "threejs.png",
 * )
 */
class ThreeJS extends File {

  /**
   * {@inheritdoc}
   */
  public function createSourceField(MediaTypeInterface $type) {
    return parent::createSourceField($type)->set('settings', ['file_extensions' => 'json']);
  }

}
