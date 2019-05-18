<?php

namespace Drupal\flush_single_image;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;

/**
 * Class FlushSingleImage.
 */
class FlushSingleImage implements FlushSingleImageInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\File\FileSystem definition.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * Constructs a new FlushSingleImage object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, FileSystemInterface $file_system) {
    $this->entityTypeManager = $entity_type_manager;
    $this->fileSystem = $file_system;
  }

  /**
   * {inheritdoc}
   */
  public function flush($path) {

    $style_paths = $this->getStylePaths($path);

    foreach ($style_paths as $style_path) {
      $this->fileSystem->unlink($style_path);
    }

    return $style_paths;

  }

  /**
   * {inheritdoc}
   */
  public function getStylePaths($path) {

    $path = $this->buildUri($path);
    $styles = $this->entityTypeManager->getStorage('image_style')->loadMultiple();
    $style_paths = [];

    foreach ($styles as $style) {
      $style_path = $style->buildUri($path);
      if (is_file($style_path) && file_exists($style_path)) {
        $style_paths[] = $style_path;
      }
    }

    return $style_paths;
  }

  /**
   * Build a URI for a given path if it's missing the scheme.
   */
  protected function buildUri($path) {
    if (!$this->fileSystem->uriScheme($path)) {
      $path = file_default_scheme() . '://' . preg_replace('/^\//' ,'', $path);
    }
    return $path;
  }

}
