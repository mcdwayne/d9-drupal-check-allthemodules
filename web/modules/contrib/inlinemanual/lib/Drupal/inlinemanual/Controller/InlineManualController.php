<?php

/**
 * @file
 * Contains \Drupal\aggregator\Controller\AggregatorController.
 */

namespace Drupal\inlinemanual\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for aggregator module routes.
 */
class InlineManualController extends ControllerBase {

  /**
   * Page callback for "Refresh topics"
   */
  public function refresh() {
    if (inlinemanual_topics_fetch_all()) {
      drupal_set_message($this->t('Topics were successfully refreshed.'));
    }
    else {
      drupal_set_message($this->t('Topics refresh failed. Please try again or see the <a href="@reports">last reports</a> to get more info.', array('@reports' => url('admin/reports/dblog'))), 'error');
    }

    return $this->redirect('inlinemanual.topics');
  }
}
