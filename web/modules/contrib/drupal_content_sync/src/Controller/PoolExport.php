<?php

namespace Drupal\drupal_content_sync\Controller;

use Drupal\Core\Url;
use Drupal\Core\Controller\ControllerBase;
use Drupal\drupal_content_sync\ApiUnifyPoolExport;

/**
 * Push changes controller.
 */
class PoolExport extends ControllerBase {

  /**
   * Export pool.
   */
  public function export($dcs_pool) {
    /**
     * @var \Drupal\drupal_content_sync\Entity\Pool $pool
     */
    $pool = \Drupal::entityTypeManager()
      ->getStorage('dcs_pool')
      ->load($dcs_pool);

    $exporter = new ApiUnifyPoolExport($pool);

    $steps      = $exporter->prepareBatch();
    $operations = [];
    foreach ($steps as $step) {
      $operations[] = [
        '\Drupal\drupal_content_sync\Controller\PoolExport::batchExport',
        [$dcs_pool, $step],
      ];
    }

    $batch = [
      'title' => t('Export configuration'),
      'operations' => $operations,
      'finished' => '\Drupal\drupal_content_sync\Controller\PoolExport::batchExportFinished',
    ];
    batch_set($batch);

    return batch_process(Url::fromRoute('entity.dcs_pool.collection'));
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
      $message = t('Pool has been exported.');
    }
    else {
      $message = t('Pool export failed.');
    }

    drupal_set_message($message);
  }

  /**
   * Batch export callback for the pool export.
   *
   * @param $dcs_pool
   * @param $operation
   */
  public static function batchExport($dcs_pool, $operation, &$context) {
    $message = 'Exporting...';
    $results = [];
    if (isset($context['results'])) {
      $results = $context['results'];
    }

    /**
     * @var \Drupal\drupal_content_sync\Entity\Pool $pool
     */
    $pool = \Drupal::entityTypeManager()
      ->getStorage('dcs_pool')
      ->load($dcs_pool);

    $exporter = new ApiUnifyPoolExport($pool);
    $results[] = $exporter->executeBatch($operation);

    $context['message'] = $message;
    $context['results'] = $results;
  }

}
