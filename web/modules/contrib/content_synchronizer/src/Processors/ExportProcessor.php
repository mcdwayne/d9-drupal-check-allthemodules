<?php

namespace Drupal\content_synchronizer\Processors;

use Drupal\content_synchronizer\Processors\Entity\EntityProcessorPluginManager;
use Drupal\Core\Entity\EntityInterface;

/**
 * Export Processor.
 */
class ExportProcessor {

  /**
   * The current export processor.
   *
   * @var ExportProcessor
   */
  static private $currentExportProcessor;

  /**
   * The writer.
   *
   * @var ExportEntityWriter
   */
  protected $writer;

  /**
   * The entity processor plugiin manager.
   *
   * @var \Drupal\content_synchronizer\Processors\Entity\EntityProcessorPluginManager
   */
  protected $entityProcessorPluginManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(ExportEntityWriter $writer) {
    $this->writer = $writer;
    $this->entityProcessorPluginManager = \Drupal::service(EntityProcessorPluginManager::SERVICE_NAME);
  }

  /**
   * Export the list of entities.
   */
  public function exportEntitiesList(array $entities) {
    self::$currentExportProcessor = $this;

    foreach ($entities as $entity) {
      // Get the plugin of the entity :
      /** @var \Drupal\content_synchronizer\Processors\Entity\EntityProcessorBase $plugin */
      $plugin = $this->entityProcessorPluginManager->getInstanceByEntityType($entity->getEntityTypeId());
      $plugin->export($entity);
      $this->writer->addRootEntity($entity);
    }

  }

  /**
   * Export the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   THe entity to export.
   */
  public function exportEntity(EntityInterface $entity) {
    $this->exportEntitiesList([$entity]);
  }

  /**
   * Get the current Export Processor.
   *
   * @return \Drupal\content_synchronizer\Processors\ExportProcessor
   *   The current export processor.
   */
  public static function getCurrentExportProcessor() {
    return self::$currentExportProcessor;
  }

  /**
   * Get the writer.
   *
   * @return \Drupal\content_synchronizer\Processors\ExportEntityWriter
   *   The writer.
   */
  public function getWriter() {
    return $this->writer;
  }

  /**
   * Delete the unzip files after process.
   */
  public function closeProcess() {
    $this->writer->archiveFiles();
    if ($archive = $this->writer->getArchiveUri()) {
      return $archive;
    }
  }

}
