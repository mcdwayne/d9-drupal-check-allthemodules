<?php

namespace Drupal\content_synchronizer\Processors;

use Drupal\content_synchronizer\Entity\ImportEntity;
use Drupal\content_synchronizer\Processors\Entity\EntityProcessorPluginManager;

/**
 * Import processor.
 */
class ImportProcessor {

  const PUBLICATION_REVISION = 'publication_revision';
  const PUBLICATION_UNPUBLISH = 'publication_unpublish';
  const PUBLICATION_PUBLISH = 'publication_publish';

  const UPDATE_SYSTEMATIC = 'update_systematic';
  const UPDATE_IF_RECENT = 'update_if_recent';
  const UPDATE_NO_UPDATE = 'update_no_update';

  /**
   * The current import processor.
   *
   * @var ImportProcessor
   */
  static private $currentImportProcessor;

  /**
   * The import entity to treat.
   *
   * @var \Drupal\content_synchronizer\Entity\ImportEntity
   */
  protected $import;

  /**
   * The entity processor plugin manager.
   *
   * @var \Drupal\content_synchronizer\Processors\Entity\EntityProcessorPluginManager
   */
  protected $entityProcessorPluginManager;

  protected $creationType;
  protected $updateType;

  /**
   * {@inheritdoc}
   */
  public function __construct(ImportEntity $import) {
    $this->import = $import;
    $this->entityProcessorPluginManager = \Drupal::service(EntityProcessorPluginManager::SERVICE_NAME);
  }

  /**
   * Import the entity from the root data of the import.
   */
  public function importEntityFromRootData(array $rootData) {
    self::$currentImportProcessor = $this;

    // Get the plugin of the entity :
    /** @var \Drupal\content_synchronizer\Processors\Entity\EntityProcessorBase $plugin */
    $plugin = $this->entityProcessorPluginManager->getInstanceByEntityType($rootData[ExportEntityWriter::FIELD_ENTITY_TYPE_ID]);
    if ($entityData = $this->import->getEntityDataFromGid($rootData[ExportEntityWriter::FIELD_GID])) {
      $plugin->import($entityData);
    }
  }

  /**
   * Get the current Import Processor.
   *
   * @return \Drupal\content_synchronizer\Processors\ImportProcessor
   *   The current import processor
   */
  public static function getCurrentImportProcessor() {
    return self::$currentImportProcessor;
  }

  /**
   * Get the import.
   *
   * @return \Drupal\content_synchronizer\Entity\ImportEntity
   *   The import entity.
   */
  public function getImport() {
    return $this->import;
  }

  /**
   * Get the creation type.
   *
   * @return mixed
   *   The creation type.
   */
  public function getCreationType() {
    return $this->creationType;
  }

  /**
   * Set the creation type.
   *
   * @param mixed $creationType
   *   The creation type.
   */
  public function setCreationType($creationType) {
    $this->creationType = $creationType;
  }

  /**
   * The update type.
   *
   * @return mixed
   *   The update type.
   */
  public function getUpdateType() {
    return $this->updateType;
  }

  /**
   * The update type.
   *
   * @param mixed $updateType
   *   The update type.
   */
  public function setUpdateType($updateType) {
    $this->updateType = $updateType;
  }

}
