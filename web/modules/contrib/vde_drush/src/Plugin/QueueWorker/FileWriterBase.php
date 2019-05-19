<?php

/**
 * @file
 * Contains Drupal\npq\Plugin\QueueWorker\NodePublishBase.php
 */

namespace Drupal\vde_drush\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\views\ViewExecutable;


/**
 * Provides base functionality for the NodePublish Queue Workers.
 */
abstract class FileWriterBase extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $formatManipulator = $data['format_manipulator'];
    $view = $data['view'];
    $export_count = $data['export_count'];
    $items_per_batch = $data['items_per_batch'];
    $output_file = $data['output_file'];
    $export_items = $data['export_items'];

    // It is necessary to have $view->query for future use
    // into $this->renderViewChunk.
    $view->build();

    $render = $this->renderViewChunk($view, $export_count, $items_per_batch);

    // Write results to file.
    $formatManipulator->handle($output_file, $render, $export_count, $export_items);

    // Release the cache.
    $this->entityCacheClear($view);
  }

  /**
   * Performs view chunk rendering.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   A view the result of which must be rendered.
   * @param int $start_index
   *   Start query index.
   * @param int $items_count
   *   Export items count.
   *
   * @return string
   *   Rendered data export display markup.
   */
  private function renderViewChunk(ViewExecutable &$view, $start_index, $items_count) {
    // Set query offset.
    $view->query->setOffset($start_index);
    $view->query->setLimit($items_count);

    // Force the view to be executed.
    $view->executed = FALSE;
    $view->build();

    // Render the view output.
    $view_render = $view->render();

    return (string) $view_render['#markup'];
  }

  /**
   * Performs views related cached entity references abolition.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   A view object cache of which must be cleared out.
   */
  private function entityCacheClear(ViewExecutable &$view) {
    // Release view result references.
    $view->result = [];
  }

}