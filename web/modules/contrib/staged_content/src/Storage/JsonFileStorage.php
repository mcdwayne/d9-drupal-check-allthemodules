<?php

namespace Drupal\staged_content\Storage;

use Drupal\staged_content\DataProxy\JsonDataProxy;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Store the data in json files in separate folders based on the marker.
 *
 * @TODO Make this class more DRY with the standard JsonFileStorage class.
 */
class JsonFileStorage implements StorageHandlerInterface {

  /**
   * Output folder root.
   *
   * @var string
   *   The root output folder.
   */
  protected $outputFolder;

  /**
   * Define which submarked dirs should be imported.
   *
   * @var string[]
   *   All the submarked dirs that should be imported.
   */
  protected $markers;

  /**
   * Filesystem helper.
   *
   * @var \Symfony\Component\Filesystem\Filesystem
   *   Filesystem helper.
   */
  protected $fileSystem;

  /**
   * DividedJsonFileStorage constructor.
   *
   * @param string $outputFolder
   *   The output dir for the data.
   * @param string[] $markers
   *   Array with the valid markers.
   * @param array $config
   *   Array with all the extra config.
   */
  public function __construct(string $outputFolder, array $markers = [], array $config = []) {

    // The pattern for the output folders based on the marker. Supports the
    // MARKER_NAME substitution. So we'll prepopulate the set here.
    $this->outputFolder = $outputFolder;
    $this->fileSystem = new Filesystem();
    $this->markers = !empty($markers) ? $markers : ['prod'];
  }

  /**
   * {@inheritdoc}
   */
  public function storeData(string $data, string $entityType, string $uuid, string $marker = NULL) {

    $this->fileSystem->mkdir($this->generateOutputFolder($marker) . '/' . $entityType);

    file_put_contents($this->generateFileName($entityType, $uuid, $marker), $data);

    // @TODO, improve output logging.
    echo '  Saved data for ' . $uuid . "\n";
  }

  /**
   * {@inheritdoc}
   */
  public function listDataItems() {
    $return = [];

    // We'll detect the entity types in all the subfolders.
    foreach ($this->generateOutputFolderList() as $marker => $outputFolder) {
      $entityTypes = glob($outputFolder . '/*', GLOB_ONLYDIR);

      foreach ($entityTypes as $entityTypeFolder) {
        $entityType = basename($entityTypeFolder);
        $sampleFiles = glob($outputFolder . '/' . $entityType . '/*.json');

        // Attach all the uuid to the array of data.
        foreach ($sampleFiles as $sampleFile) {
          $uuid = str_replace('.json', '', basename($sampleFile));
          // Since all the data is identical (or should be, it doesn't matter
          // it the marker gets overwritten if the same uuid is in 2 different
          // folders.
          $return[$uuid] = new JsonDataProxy($sampleFile, $uuid, $entityType, $marker);
        }
      }
    }

    return $return;
  }

  /**
   * Load all the data for a given file.
   *
   * @param string $entityType
   *   The entity type to load.
   * @param string $uuid
   *   The uuid for the entity to load.
   * @param string $marker
   *   The marker for this item.
   *
   * @return string
   *   Data for this entity.
   */
  public function generateFileName(string $entityType, string $uuid, string $marker) {
    $fileName = $uuid . '.json';
    return $this->generateOutputFolder($marker) . '/' . $entityType . '/' . $fileName;
  }

  /**
   * {@inheritdoc}
   */
  public function getDataItem(string $entityType, string $uuid, string $marker = '') {
    return new JsonDataProxy(
      $this->generateFileName($entityType, $uuid, $marker),
      $uuid,
      $entityType,
      $marker
    );
  }

  /**
   * Generate the location to export a file to based on the marker.
   *
   * @param string $marker
   *   Marker to generate the folder location for.
   *
   * @return string
   *   Folder location for the file.
   */
  public function generateOutputFolder(string $marker) {
    return str_replace('MARKER_NAME', $marker, $this->outputFolder);
  }

  /**
   * Generate an array with all the output folders.
   */
  public function generateOutputFolderList() {
    $outputFolders = [];
    foreach ($this->markers as $marker) {
      $outputFolders[$marker] = $this->generateOutputFolder($marker);
    }
    return $outputFolders;
  }

  /**
   * Get the root output folder.
   *
   * @return string
   *   The output folder.
   */
  public function getOutputFolder() {
    return $this->outputFolder;
  }

  /**
   * Set the output folder.
   *
   * @param string $outputFolder
   *   The output folder.
   */
  public function setOutputFolder(string $outputFolder) {
    $this->outputFolder = $outputFolder;
  }

}
