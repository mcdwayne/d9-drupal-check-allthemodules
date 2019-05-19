<?php

namespace Drupal\yaml_content\Event;

use Drupal\yaml_content\ContentLoader\ContentLoaderInterface;

/**
 * Wraps a yaml content post-import event for event listeners.
 */
class PostImportEvent extends EventBase {

  /**
   * The list of content files imported in the completed batch.
   *
   * @var array
   */
  protected $importFiles;

  /**
   * An associative array of imported content entities keyed by file name.
   *
   * @var array
   */
  protected $loadedContent;

  /**
   * Constructs a yaml content post-import event object.
   *
   * @param \Drupal\yaml_content\ContentLoader\ContentLoaderInterface $loader
   *   The active Content Loader that triggered the event.
   * @param array $import_files
   *   An array of files imported in the completed batch.
   * @param array $loaded_content
   *   An associative array of imported content entities keyed by file name.
   */
  public function __construct(ContentLoaderInterface $loader, array $import_files, array $loaded_content) {
    parent::__construct($loader);

    $this->importFiles = $import_files;
  }

  /**
   * Gets the list of files imported in the completed batch.
   *
   * @return array
   *   The list of content files imported in the completed batch.
   */
  public function getImportFiles() {
    return $this->importFiles;
  }

  /**
   * Gets the list of imported content entities.
   *
   * @return array
   *   An associative array of imported content entities keyed by file name.
   */
  public function getLoadedContent() {
    return $this->loadedContent;
  }

}
