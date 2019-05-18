<?php

/**
 * Batch API Methods
 *
 * @file
 * Contains paragraphs_report.batch.inc.
 */

use Drupal\paragraphs_report\Controller\ParagraphsReport;


/**
 * Batch method to load node and check for para field content.
 *
 * @param array $nids of node ids to process per batch run
 * @param array $context
 * @throws
 */
function batchGetParaFields($nids, &$context) {
  $paraReport = new ParagraphsReport();
  // Initiate multistep processing and any custom batch vars.
  if (empty($context['sandbox'])) {
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['max'] = count($nids);
  }
  // Counter var.
  if (empty($context['results']['count'])) {
    $context['results']['count'] = 0;
  }
  if(!empty($nids)) {
    foreach($nids as $nid) {
      $current = isset($context['results']['paras']) ? $context['results']['paras'] : [];
      $context['results']['paras'] = $paraReport->getParasFromNid($nid, $current);
      $context['results']['count']++;
      $context['sandbox']['progress']++;
    }
    // Multistep processing : report progress.
    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
  }
}

/**
 * After batch process has completed, save results to db.
 *
 * @param $success
 * @param $results
 * @param $operations
 */
function batchSave($success, $results, $operations) {
  // JSON encode and save to module config.
  $paraReport = new ParagraphsReport();
  $paraReport->configSaveReport($results['paras']);
  if ($success) {
    $message = \Drupal::translation()
      ->formatPlural($results['count'], 'One node processed.', '@count nodes processed.');
    \Drupal::logger('my_module')->notice($message);
  }
  else {
    $message = t('Finished with an error.');
    \Drupal::logger('paragraphs_report')->error($message);
  }
  drupal_set_message($message);
}
