<?php

namespace Drupal\campaignmonitor_local\Controller;

/**
 *
 */
class CampaignMonitorLocalBatchController {

  /**
   *
   */
  public function content() {

    $batch = [
      'title' => t('Processing'),
      'operations' => [
        ['campaignmonitor_local_batch', ['subscription_queue']],
      ],
      'finished' => 'campaignmonitor_local_finished_callback',
      'file' => drupal_get_path('module', 'campaignmonitor_local') . '/campaignmonitor_local.batch.inc',
    ];

    batch_set($batch);
    return batch_process('user');
  }

}
