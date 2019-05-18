<?php

namespace Drupal\content_synchronizer\Processors;

use Drupal\content_synchronizer\Base\BatchProcessorBase;

/**
 * BatchExport processor.
 */
class BatchExportProcessor extends BatchProcessorBase {

  /**
   * The writer service.
   *
   * @var ExportEntityWriter
   */
  protected $writer;

  /**
   * The export processor.
   *
   * @var ExportProcessor
   */
  protected $exportProcessor;

  /**
   * {@inheritdoc}
   */
  public function __construct(ExportEntityWriter $writer) {
    $this->writer = $writer;
  }

  /**
   * Export entities.
   *
   * @param array $entities
   *   Entities to export.
   * @param mixed $finishCallback
   *   Callback method.
   */
  public function exportEntities(array $entities, $finishCallback = NULL) {
    $operations = $this->getBatchOperations($entities, $finishCallback);

    $batch = [
      'title'      => t('Exporting entities...'),
      'operations' => $operations,
      'finished'   => get_called_class() . '::onFinishBatchProcess',
    ];

    batch_set($batch);
  }

  /**
   * {@inheritdoc}
   */
  protected function getBatchOperations(array $entities, $finishCallback = NULL) {
    $operations = [];
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    foreach ($entities as $entity) {
      $operations[] = [
        get_called_class() . '::processBatchOperation',
        [
          [
            'entity_id'      => $entity->id(),
            'entity_type'    => $entity->getEntityTypeId(),
            'writer'         => $this->writer,
            'finishCallback' => $finishCallback,
          ],
        ],
      ];
    }

    return $operations;
  }

  /**
   * Do a batch operation.
   *
   * @param array $entityData
   *   Entity data.
   * @param array $context
   *   Context.
   */
  public static function processBatchOperation(array $entityData, array $context) {
    /** @var ExportEntityWriter $writer */
    $writer = $entityData['writer'];

    // Get the entity :
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    if ($entity = \Drupal::entityTypeManager()
      ->getStorage($entityData['entity_type'])
      ->load($entityData['entity_id'])
    ) {
      /** @var ExportProcessor $processor */
      $processor = new ExportProcessor($writer);
      $processor->exportEntity($entity);
    }

    $context['results']['writer'] = $writer;
    $context['results']['finishCallback'] = $entityData['finishCallback'];
  }

  /**
   * {@inheritdoc}
   */
  public static function onFinishBatchProcess($success, $results, $operations) {
    /** @var ExportEntityWriter $writer */
    $writer = $results['writer'];

    $processor = new ExportProcessor($writer);
    if ($archiveUri = $processor->closeProcess()) {
      // Redirection :
      if (array_key_exists('finishCallback', $results)) {
        self::callFinishCallback($results['finishCallback'], $archiveUri);
      }
    }
  }

}
