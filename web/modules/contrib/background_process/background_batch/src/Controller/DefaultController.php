<?php

 namespace Drupal\background_batch\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Default controller for the background_batch module.
 */
class DefaultController extends ControllerBase {

  /**
   * Implements Background Batch Overview Page.
   */
  public function backgroundBatchOverviewPage() {
    $data = [];
    $bids = db_select('batch', 'b')
      ->fields('b', ['bid'])
      ->orderBy('b.bid', 'ASC')
      ->execute()
      ->fetchAllKeyed(0, 0);
    foreach ($bids as $bid) {
      $progress = progress_get_progress('_background_batch:' . $bid);

      if (!$progress) {
        $progress = (object) [
          'start' => 0,
          'end' => 0,
          'progress' => 0,
          'message' => $this->t('N/A'),
        ];
      }
      $eta = progress_estimate_completion($progress);
      $data[] = [
        $progress->end ? $bid : $this->l($bid, Url::fromRoute('system.batch_page.html')),
        sprintf("%.2f%%", $progress->progress * 100),
        $progress->message,
        $progress->start ? \Drupal::service('date.formatter')->format((int) $progress->start, 'small') : $this->t('N/A'),
        $progress->end ? \Drupal::service('date.formatter')->format((int) $progress->end, 'small') : ($eta ? \Drupal::service('date.formatter')->format((int) $eta, 'small') : $this->t('N/A')),
      ];
    }
    $header = ['Batch ID', 'Progress', 'Message', 'Started', 'Finished/ETA'];
    $markup = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $data,
    ];

    return $markup;
  }

}
