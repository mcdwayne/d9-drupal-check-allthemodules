<?php

namespace Drupal\drupal_content_sync\Controller;

use Drupal\Core\Url;
use Drupal\Core\Controller\ControllerBase;
use Drupal\drupal_content_sync\ApiUnifyFlowExport;

/**
 * Push changes controller.
 */
class FlowExport extends ControllerBase {

  /**
   * Export flow.
   */
  public function export($dcs_flow) {
    /**
     * @var \Drupal\drupal_content_sync\Entity\Flow $flow
     */
    $flow = \Drupal::entityTypeManager()
      ->getStorage('dcs_flow')
      ->load($dcs_flow);

    $exporter = new ApiUnifyFlowExport($flow);

    $steps      = $exporter->prepareBatch();
    $operations = [];
    foreach ($steps as $step) {
      $operations[] = [
        '\Drupal\drupal_content_sync\Controller\FlowExport::batchExport',
        [$dcs_flow, $step],
      ];
    }

    $batch = [
      'title' => t('Export configuration'),
      'operations' => $operations,
      'finished' => '\Drupal\drupal_content_sync\Controller\FlowExport::batchExportFinished',
    ];
    batch_set($batch);

    return batch_process(Url::fromRoute('entity.dcs_flow.collection'));
  }

  /**
   * Batch export finished callback.
   *
   * @param $success
   * @param $results
   * @param $operations
   */
  public static function batchExportFinished($success, $results, $operations) {
    if ($success) {
      $message = t('Flow has been exported.');
    }
    else {
      $message = t('Flow export failed.');
    }

    drupal_set_message($message);
  }

  /**
   * Batch export callback for the flow export.
   *
   * @param $ids
   * @param $context
   */
  public static function batchExport($dcs_flow, $operation, &$context) {
    $message = 'Exporting...';
    $results = [];
    if (isset($context['results'])) {
      $results = $context['results'];
    }

    /**
     * @var \Drupal\drupal_content_sync\Entity\Flow $flow
     */
    $flow = \Drupal::entityTypeManager()
      ->getStorage('dcs_flow')
      ->load($dcs_flow);

    $exporter = new ApiUnifyFlowExport($flow);
    $results[] = $exporter->executeBatch($operation);

    $context['message'] = $message;
    $context['results'] = $results;
  }

}
