<?php

namespace Drupal\content_synchronizer\Plugin\content_synchronizer\entity_processor;

use Drupal\content_synchronizer\Base\JsonWriterTrait;
use Drupal\content_synchronizer\Processors\Entity\EntityProcessorBase;
use Drupal\content_synchronizer\Processors\Entity\EntityProcessorInterface;
use Drupal\content_synchronizer\Processors\ExportProcessor;
use Drupal\content_synchronizer\Processors\ImportProcessor;
use Drupal\Core\Entity\EntityInterface;
use Drupal\file\Entity\File;

/**
 * Plugin implementation of the 'accordion' formatter.
 *
 * @EntityProcessor(
 *   id = "content_synchronizer_file_processor",
 *   entityType = "file"
 * )
 */
class FileProcessor extends EntityProcessorBase implements EntityProcessorInterface {
  use JsonWriterTrait;
  const DIR_ASSETS = "assets";


  protected $exportAssetsDirPath;
  protected $importAssetsDirPath;

  /**
   * Return the data to export.
   */
  public function getDataToExport(EntityInterface $entityToExport) {
    $this->addFileToAssets($entityToExport);
    return parent::getDataToExport($entityToExport);
  }

  /**
   * Add file to assets dir.
   */
  protected function addFileToAssets(File $file) {
    $assetsDir = $this->getExportAssetsDir();

    $destination = $this->createDirTreeForFileDest(str_replace('://', '/', $file->getFileUri()), $assetsDir);

    // Copy file in destination directory.
    file_copy($file, $destination);
  }

  /**
   * Return the export assets path.
   */
  protected function getExportAssetsDir() {
    if (!$this->exportAssetsDirPath) {
      $writer = ExportProcessor::getCurrentExportProcessor()->getWriter();
      $this->exportAssetsDirPath = $writer->getDirPath() . '/' . self::DIR_ASSETS;
      $this->createDirectory($this->exportAssetsDirPath);
    }
    return $this->exportAssetsDirPath;
  }

  /**
   * Return the import assets path.
   */
  protected function getImportAssetsDir() {
    if (!$this->importAssetsDirPath) {
      $this->importAssetsDirPath = ImportProcessor::getCurrentImportProcessor()->getImport()->getArchiveFilesPath() . '/' . self::DIR_ASSETS;

    }
    return $this->importAssetsDirPath;
  }

  /**
   * Return the entity to import.
   *
   * @param array $data
   *   The data to import.
   * @param \Drupal\Core\Entity\Entity|null $entityToImport
   *   The entity to import.
   *
   * @return \Drupal\Core\Entity\Entity|\Drupal\Core\Entity\Entity\null|\Drupal\Core\Entity\EntityInterface
   *   The entity to import.
   */
  public function getEntityToImport(array $data, EntityInterface $entityToImport = NULL) {
    if ($file = parent::getEntityToImport($data, $entityToImport)) {
      $assetsFile = $this->getImportAssetsDir() . '/' . str_replace('://', '/', $file->getFileUri());
      if (file_exists($assetsFile)) {

        if (strpos($file->getFileUri(), '://')) {
          list($root, $destination) = explode('://', $file->getFileUri());
          $root .= '://';
        }
        else {
          list($root, $destination) = [$file->getFileUri(), '/'];
        }

        $destination = $this->createDirTreeForFileDest($destination, $root);

        if ($result = copy($assetsFile, $file->getFileUri())) {
          return $file;
        }
      }
    }

    return NULL;
  }

}
