<?php

namespace Drupal\content_synchronizer\Processors;

use Cocur\Slugify\Slugify;
use Drupal\content_synchronizer\Base\JsonWriterTrait;
use Drupal\Core\Archiver\ArchiveTar;
use Drupal\Core\Entity\EntityInterface;

/**
 * Export entity writer.
 */
class ExportEntityWriter {

  use JsonWriterTrait;

  const TYPE_EXTENSION = '.json';
  const EXPORT_EXTENSION = '.tar.gz';
  const ROOT_FILE_NAME = 'root';
  const GENERATOR_DIR = 'temporary://content_synchronizer/';


  const FIELD_GID = 'gid';
  const FIELD_UUID = 'uuid';
  const FIELD_CHANGED = 'changed';
  const FIELD_ENTITY_TYPE_ID = 'entity_type_id';
  const FIELD_ENTITY_ID = 'entity_id';
  const FIELD_LABEL = 'label';

  protected $id;

  /**
   * Slugifier.
   *
   * @var \Cocur\Slugify\Slugify
   */
  protected $slugifier;

  /**
   * ExportEntityWriter constructor.
   */
  public function __construct() {
    $this->slugifier = new Slugify();
  }

  /**
   * THe id of the directory.
   */
  public function initFromId($id) {
    $this->id = $id . '.' . time() . rand();
  }

  /**
   * Return the directory path.
   */
  public function getDirPath() {
    return self::GENERATOR_DIR . 'export/' . $this->id;
  }

  /**
   * Write the document to export.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entityToExport
   *   The entity to export.
   * @param array $dataToExport
   *   The data to export.
   */
  public function write(EntityInterface $entityToExport, array $dataToExport) {
    if (array_key_exists('gid', $dataToExport) && $gid = $dataToExport['gid']) {

      // Get the previous exported entities :
      if ($allExportedData = $this->getExportedData($entityToExport)) {
        // If the current entity has already been exported, the writer don't do anything.
        if (array_key_exists($gid, $allExportedData)) {
          return;
        }
      }
      else {
        $allExportedData = [];
      }

      // Add the current entity to export to the already exported data.
      $allExportedData[$gid] = $dataToExport;

      // Write files :
      $this->writeJson($allExportedData, $this->getExpotedDataTypeFilePath($entityToExport));
    }
  }

  /**
   * Get the entity Type data already exported.
   *
   * @param string $entityType
   *   The entityType.
   *
   * @return mixed
   *   The data.
   */
  public function getExportedData($entityType) {
    $path = $this->getExpotedDataTypeFilePath($entityType);
    return $this->getDataFromFile($path);
  }

  /**
   * Add the entity to the exported root entities list.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The root entity to add.
   */
  public function addRootEntity(EntityInterface $entity) {
    $rootEntitiesFilePath = $this->getDirPath() . '/' . self::ROOT_FILE_NAME . self::TYPE_EXTENSION;
    $rootEntities = $this->getDataFromFile($rootEntitiesFilePath);
    if (!$rootEntities) {
      $rootEntities = [];
    }

    // Add entity data.
    $rootEntities[] = [
      self::FIELD_GID            => $entity->contentSynchronizerGid,
      self::FIELD_ENTITY_TYPE_ID => $entity->getEntityTypeId(),
      self::FIELD_ENTITY_ID      => $entity->id(),
      self::FIELD_LABEL          => static::getEntityLabel($entity),
      self::FIELD_UUID           => $entity->uuid(),
    ];

    $this->writeJson($rootEntities, $rootEntitiesFilePath);
  }

  /**
   * Return the entity label.
   */
  public static function getEntityLabel(EntityInterface $entity) {
    return $entity->label();
  }

  /**
   * Return the file path of the type of the entity.
   */
  public function getExpotedDataTypeFilePath(EntityInterface $entity) {
    return $this->getDirPath() . '/' . $entity->getEntityTypeId() . self::TYPE_EXTENSION;
  }

  /**
   * Return the archive path.
   */
  protected function getArchivePath() {
    return $this->getDirPath() . self::EXPORT_EXTENSION;
  }

  /**
   * Zip the generated files.
   */
  public function archiveFiles() {
    $path = \Drupal::service('file_system')
      ->realpath($this->getDirPath());

    $archivePath = $path . static::EXPORT_EXTENSION;

    /** @var \Drupal\Core\File\FileSystemInterface $fileSystem */
    $fileSystem = \Drupal::service('file_system');
    $fileSystem->delete($archivePath);
    $archiver = new ArchiveTar($archivePath, 'gz');

    $this->addRepToArchive($path, '', $archiver);
  }

  /**
   * Add files to archive recursively.
   *
   * @param string $repPath
   *   The repertory path.
   * @param string $parent
   *   The parent.
   * @param \Drupal\Core\Archiver\ArchiveTar $archiver
   *   The archiver.
   */
  public function addRepToArchive($repPath, $parent, ArchiveTar $archiver) {
    $files = \scandir($repPath);
    foreach ($files as $file) {
      if (!in_array($file, ['.', '..'])) {
        if (is_dir($repPath . '/' . $file)) {
          $this->addRepToArchive($repPath . '/' . $file, $parent . '/' . $file, $archiver);
        }
        else {
          $archiver->addString($parent . '/' . $file, file_get_contents($repPath . '/' . $file));
        }
      }
    }

  }

  /**
   * Return the archive uri.
   */
  public function getArchiveUri() {
    $archivePath = $this->getArchivePath();
    if (file_exists($archivePath)) {
      return $archivePath;
    }
    return FALSE;
  }

  /**
   * The writer id.
   *
   * @return string
   *   THe id.
   */
  public function getId() {
    return $this->id;
  }

}
