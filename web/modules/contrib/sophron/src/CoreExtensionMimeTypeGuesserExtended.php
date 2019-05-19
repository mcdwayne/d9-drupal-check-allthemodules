<?php

namespace Drupal\sophron;

use Drupal\Core\File\MimeType\ExtensionMimeTypeGuesser;

/**
 * @todo
 */
class CoreExtensionMimeTypeGuesserExtended extends ExtensionMimeTypeGuesser {

  /**
   * Constructs a new CoreExtensionMimeTypeGuesserExtended.
   */
  public function __construct() {
    $this->moduleHandler = \Drupal::service('module_handler');
  }

  /**
   * @todo
   */
  public function listTypes() {
    return $this->getMapping()['mimetypes'];
  }

  /**
   * @todo
   */
  public function listExtensions() {
    return array_keys($this->getMapping()['extensions']);
  }

  /**
   * @todo
   */
  protected function getMapping() {
    if ($this->mapping === NULL) {
      $mapping = $this->defaultMapping;
      // Allow modules to alter the default mapping.
      $this->moduleHandler->alter('file_mimetype_mapping', $mapping);
      $this->mapping = $mapping;
    }
    return $this->mapping;
  }

}
