<?php

/**
 * @file
 * @file
 * @file
 * .*/

/**
 *
 */
function campaignmonitor_local_batch($type, &$context) {
  if (empty($context['sandbox'])) {
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['current_id'] = 0;
  }
  switch ($type) {

    case 'subscription_queue':
      campaignmonitor_local_subscription_queue($context);
      break;

    default:
      $message = 'migrating something else...';
      break;

  }
}

/**
 * Add missing field values.
 *
 * @param $context
 */
function campaignmonitor_local_subscription_queue(&$context) {
  if (empty($context['sandbox']['max'])) {
    $context['sandbox']['max'] = db_query("SELECT COUNT(DISTINCT item_id) FROM {queue} WHERE name='campaignmonitor_local_subscriptions'")->fetchField();
  }
  $limit = 10;
  $query = \Drupal::database()->select('queue', 'q');
  // $query->join('content_field_image_image', 'cfii', 'cti.field_image_image_fid = cfii.field_image_image_fid');
  //  $query->leftJoin('term_node', 'tn', 'cti.nid = tn.nid');
  // $query->leftJoin('term_data', 'td', 'tn.tid = td.tid');.
  $query->fields('q', ['item_id', 'data'])
    // ->fields('td', ['vid'])
    ->condition('name', 'campaignmonitor_local_subscriptions')
    ->condition('q.item_id', $context['sandbox']['current_id'], '>')
    ->orderBy('q.item_id')
    ->range(0, $limit);

  $result = $query->execute();

  foreach ($result as $row) {
    $context['results'][] = $row->item_id;
    $context['sandbox']['progress']++;
    $context['sandbox']['current_id'] = $row->item_id;
    $context['message'] = 'queue Id ' . $row->item_id;
    $data = unserialize($row->data);
    $uid = $data['uid'];
    $mail = $data['mail'];
    // Get the CM subscription.
    $subs = campaignmonitor_user_get_user_subscriptions($mail);
    // Merge the data record.
    campaignmonitor_local_insert_user_data($uid, $subs);

    \Drupal::database()->delete('queue')
      ->condition('item_id', $row->item_id)
      ->execute();

  }
  if ($context['sandbox']['progress'] < $context['sandbox']['max']) {
    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
  }
  else {
    $context['finished'] = 1;
  }

  \Drupal::service('page_cache_kill_switch')->trigger();
}

/**
 *
 */
function campaignmonitor_local_finished_callback($success, $results, $operations) {
  // The 'success' parameter means no fatal PHP errors were detected. All
  // other error management should be handled using 'results'.
  if ($success) {
    if (isset($results['users'])) {
      $message = \Drupal::translation()->formatPlural(
        count($results['users']),
        'One user processed.', '@count users processed.'
      );

      $message .= 'highest uid reached ' . $results['last'] . '<br />';
      $message .= 'highest uid ' . $results['highest'];
    }
    else {
      $message = \Drupal::translation()->formatPlural(
        count($results),
        'One user processed.', '@count user processed.'
      );
    }

  }
  else {
    $message = t('Finished with an error.');
  }
  // drupal_set_message('Results <pre>' . print_r($results['images'],1));.
  drupal_set_message($message);
}
