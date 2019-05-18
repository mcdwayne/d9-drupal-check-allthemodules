<?php

namespace Drupal\content_synchronizer\Processors;

use Drupal\content_synchronizer\Base\BatchProcessorBase;
use Drupal\content_synchronizer\Entity\ImportEntity;

/**
 * Batch Import.
 */
class BatchImportProcessor extends BatchProcessorBase {

  /**
   * The entities to import.
   *
   * @var array
   */
  protected $entitiesToImport;

  /**
   * Launch the import of the import entity.
   *
   * @param \Drupal\content_synchronizer\Entity\ImportEntity $import
   *   The import entity.
   * @param array $entitiesToImport
   *   The entities to import.
   * @param mixed $finishCallBack
   *   The finishcallback.
   * @param string $creationType
   *   The creation entity type.
   * @param string $updateType
   *   The update entity type.
   */
  public function import(ImportEntity $import, array $entitiesToImport, $finishCallBack = NULL, $creationType = ImportProcessor::PUBLICATION_UNPUBLISH, $updateType = ImportProcessor::UPDATE_IF_RECENT) {
    $operations = $this->getBatchOperations($import, $entitiesToImport, $finishCallBack, $creationType, $updateType);

    $batch = [
      'title'      => t('Importing entities...'),
      'operations' => $operations,
      'finished'   => get_called_class() . '::onFinishBatchProcess',
    ];

    batch_set($batch);
  }

  /**
   * Set the entities to import list.
   */
  public function setEntitiesToImport(array $entitiesToImport) {
    $this->entitiesToImport = $entitiesToImport;
  }

  /**
   * {@inheritdoc}
   */
  protected function getBatchOperations(ImportEntity $import, array $entitiesToImport, $finishCallBack = NULL, $creationType = ImportProcessor::PUBLICATION_UNPUBLISH, $updateType = ImportProcessor::UPDATE_IF_RECENT) {
    $operations = [];

    foreach ($entitiesToImport as $data) {
      $data['finishCallback'] = $finishCallBack;
      $data['importId'] = $import->id();
      $data['creationType'] = $creationType;
      $data['updateType'] = $updateType;
      $operations[] = [
        get_called_class() . '::processBatchOperation',
        [$data],
      ];
    }

    return $operations;
  }

  /**
   * Do a batch operation.
   *
   * @param array $data
   *   The data to treat.
   * @param array $context
   *   The context.
   */
  public static function processBatchOperation(array $data, array $context) {
    $importProcessor = new ImportProcessor(ImportEntity::load($data['importId']));
    $importProcessor->setCreationType($data['creationType']);
    $importProcessor->setUpdateType($data['updateType']);
    $importProcessor->importEntityFromRootData($data);

    $context['results']['finishCallback'] = $data['finishCallback'];
  }

  /**
   * Callback.
   */
  public static function onFinishBatchProcess($success, $results, $operations) {

    if (array_key_exists('finishCallback', $results)) {
      self::callFinishCallback($results['finishCallback'], $results);
    }
  }

}
