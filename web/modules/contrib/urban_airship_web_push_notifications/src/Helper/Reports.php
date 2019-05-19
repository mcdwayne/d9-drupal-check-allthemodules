<?php

namespace Drupal\urban_airship_web_push_notifications\Helper;

/**
 * Get notifications report.
 */
class Reports {

  /**
   * Load all sent notification for given node ID and return 
   * results in a table.
   */
  public function loadAll($nid) {
    $header = array();
    $rows = array();
    $results = db_select('urban_airship_web_push_notifications', 'n')
      ->fields('n')
      ->condition('n.nid', $nid)
      ->orderBy('n.sent_at', 'DESC')
      ->execute();
    foreach ($results as $row) {
      $rows[] = [$row->notification_text, format_date($row->sent_at), $row->username];
    }
    return [
      'count' => count($rows),
      'table' => [
        '#type'   => 'table',
        '#header' => $header,
        '#rows'   => $rows,
        '#weight' => 21,
      ],
    ];
  }

}
