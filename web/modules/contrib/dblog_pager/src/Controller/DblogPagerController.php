<?php

namespace Drupal\dblog_pager\Controller;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\dblog\Controller\DbLogController;

/**
 * Returns response for dblog_pager overridden route (dblog.event).
 */
class DblogPagerController extends DbLogController {

  /**
   * Function: get link to log record as found via the query args/filter.
   *
   * @param array $query_args
   *   Used to generate a where based on record.wid. It must have these indices:
   *     sort => ASC|DESC - the sort direction of the query.
   *     operator => string -- the operator (=/>/< etc.) to be used in the query
   *     value => int -- the value to compare to record.wid.
   * @param array $filter
   *   An array of Where conditions and associative array of values.
   * @param bool $id
   *   Controls what return value type is.
   *
   * @return array|int|\Drupal\core\Link
   *   if bool $id  is TRUE, return the ID, otherwise return formatted link.
   */
  private function getLink(array $query_args, array $filter = NULL, $id = FALSE) {
    $pos = 0;
    $query = db_select('watchdog', 'w');
    $query
      ->fields('w', ['wid'])
      ->orderBy('w.wid', $query_args['sort']);
    if (!empty($filter['where'])) {
      $pos = count($filter['args']);
      $query->where($filter['where'], $filter['args']);
    }
    $query->where("w.wid {$query_args['operator']} ?",
      [$pos => $query_args['value']]);
    $records = $query
      ->range(0, 1)
      ->execute();
    if (!$records) {
      return FALSE;
    }
    foreach ($records as $record) {
      if ($record->wid === $id) {
        continue;
      }
      if ($id === TRUE) {
        return $record->wid;
      }
      return Link::fromTextAndUrl($query_args['title'], Url::fromRoute('dblog.event', ['event_id' => $record->wid]));
    }
  }

  /**
   * Displays details about a specific database log message.
   *
   * @param int $event_id
   *   Unique ID of the database log message.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   If the ID is located in the Database Logging table, a build array in the
   *   format expected by drupal_render();
   */
  public function evDetails($event_id) {
    $output = [];
    $build = $this->eventDetails($event_id);
    $config = \Drupal::config('dblog_pager.settings');
    $filter = $this->buildFilterQuery();

    // Original implementation returns a empty array when log record not found.
    if (!$build) {
      // If we aren't overriding that we'll return an empty array, else warn.
      if (!$config->get('bad_id_override')) {
        return $build;
      }
      drupal_set_message($this->t('No event log record found for id: @id.',
       ['@id' => $event_id]), 'warning');
      if ($config->get('bad_id_last')) {
        $id = $this->getLink([
          'sort' => 'DESC',
          'operator' => '<',
          'value' => PHP_INT_MAX,
          ],
          $filter,
          TRUE);
        if (!$id) {
          drupal_set_message($this->t('There appears to be no log messages.'), 'error');
          return [];
        }
        drupal_set_message($this->t('Selected last log record to display.'), 'status');
        return $this->redirect('dblog.event', ['event_id' => $id]);
      }
      else {
        $output['table'] = FALSE;
      }
    }
    else {
      $output['table'] = $build['dblog_table'];
    }
    // Add the pager links.
    $query_data = [
      'prev' => [
        'sort' => "DESC",
        'operator' => '<',
        'value' => $event_id,
        'title' => $this->t('Previous'),
      ],
      'next' => [
        'sort' => 'ASC',
        'operator' => '>',
        'value' => $event_id,
        'title' => $this->t('Next'),
      ],
    ];
    if ($config->get('show_first_last')) {
      $query_data = [
        'first' => [
          'sort' => 'ASC',
          'operator' => '>',
          'value' => 0,
          'title' => $this->t('First'),
        ],
      ] + $query_data;
      $query_data['last'] = [
        'sort' => 'DESC',
        'operator' => '<',
        'value' => PHP_INT_MAX,
        'title' => $this->t('Last'),
      ];
    }
    $output['links'] = [
      '#theme' => 'item_list',
      '#items' => [],
      '#attributes' => ['class' => 'dblog-event-pager'],
    ];
    foreach ($query_data as $query_args) {
      $link = $this->getLink($query_args, $filter, $event_id);
      if (!$link) {
        continue;
      }
      $output['links']['#items'][] = $link;
    }
    $output['#attached'] = [
      'library' => ['dblog_pager/styling'],
    ];
    return $output;
  }

}
