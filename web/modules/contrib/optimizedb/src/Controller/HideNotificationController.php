<?php

/**
 * @file
 * Contains \Drupal\optimizedb\Controller\HideNotificationController.
 */

namespace Drupal\optimizedb\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Page hide notification.
 */
class HideNotificationController extends ControllerBase {

  /**
   * Page hide notification.
   *
   * @return string
   *   Result hide notification.
   */
  public function hide() {
    $time = REQUEST_TIME;
    $config = \Drupal::configFactory()->getEditable('optimizedb.settings');

    $notify_optimize = $config->get('optimizedb_notify_optimize');

    // There is a need to disable the notification?
    if ($notify_optimize) {
      $config
        ->set('optimizedb_notify_optimize', FALSE)
        // If the notification of the need to optimize hiding, so she runs.
        ->set('optimizedb_last_optimization', $time)
        ->save();

      $optimization_period = (int) $config->get('optimizedb_optimization_period');
      $time_next_optimization = strtotime('+ ' . $optimization_period . ' day', $time);

      $output = $this->t('The following message on the need to perform optimization, you get - @date.', array(
        '@date' => \Drupal::service('date.formatter')->format($time_next_optimization),
      ));
    }
    else {
      $output = $this->t('Alerts are not available.');
    }

    return [
      '#type' => 'markup',
      '#markup' => $output,
    ];
  }

}
