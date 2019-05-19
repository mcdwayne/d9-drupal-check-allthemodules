<?php

namespace Drupal\yaml_content\Event;

use Drupal\yaml_content\ContentLoader\ContentLoaderInterface;

/**
 * Wraps a yaml content pre-import event for event listeners.
 */
class PreImportEvent extends EventBase {

  /**
   * The list of content files to be imported in the batch about to begin.
   *
   * @var array
   */
  protected $importFiles;

  /**
   * Constructs a yaml content pre-import event object.
   *
   * @param \Drupal\yaml_content\ContentLoader\ContentLoaderInterface $loader
   *   The active Content Loader that triggered the event.
   * @param array $import_files
   *   An array of files to be imported in the batch about to begin.
   */
  public function __construct(ContentLoaderInterface $loader, array &$import_files) {
    parent::__construct($loader);

    $this->importFiles = $import_files;
  }

  /**
   * Gets the list of files to be imported in the batch about to begin.
   *
   * @return array
   *   The list of content files to be imported in the batch about to begin.
   */
  public function getImportFiles() {
    return $this->importFiles;
  }

}
