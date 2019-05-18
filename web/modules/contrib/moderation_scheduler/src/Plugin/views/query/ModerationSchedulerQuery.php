<?php

namespace Drupal\moderation_scheduler\Plugin\views\query;

use Drupal\node\NodeInterface;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\views\ResultRow;

/**
 * ModerationSchedulerQuery views query plugin expose the results to views.
 *
 * @ViewsQuery(
 *   id = "moderation_scheduler_query",
 *   title = @Translation("Moderation Scheduler Query"),
 *   help = @Translation("Query against the scheduled time field.")
 * )
 */
class ModerationSchedulerQuery extends QueryPluginBase {

  /**
   * {@inheritdoc}
   */
  public function execute(ViewExecutable $view) {
    $moderation_scheduler_interface = \Drupal::service('moderation_scheduler.services');
    $date_formatter = \Drupal::service('date.formatter');

    $nodes[] = $moderation_scheduler_interface->fieldScheduledTimeRevision(NULL, NULL);

    if ($nodes) {
      $key_array = [];
      $index = 0;

      foreach ($nodes as $access_token) {
        if ($data = $access_token) {
          foreach ($data as $d) {
            if ($d instanceof NodeInterface && !in_array($d->getTitle(), $key_array)) {
              $state = $moderation_scheduler_interface->returnState($d);
              $row['title'] = $d->getTitle();
              $row['content_type'] = $d->type->entity->label();
              $row['field_scheduled_time'] = $date_formatter->format(strtotime($d->get('field_scheduled_time')->value), 'short');
              $row['author'] = $d->getOwner()->getDisplayName();
              $row['status'] = $state;
              $row['revision'] = $d->get('vid')->value;

              // 'index' key is required.
              $row['index'] = $index++;
              $view->result[] = new ResultRow($row);
              array_push($key_array, $row['title']);
            }
          }

        }

      }
    }
  }

  /**
   * Hook_ensureTable.
   */
  public function ensureTable($table, $relationship = NULL) {
    return '';
  }

  /**
   * Hoox_addField.
   */
  public function addField($table, $field, $alias = '', $params = []) {
    return $field;
  }

  /**
   * Hoox_addOrderBy.
   */
  public function addOrderBy($table, $field = NULL, $order = 'ASC', $alias = '', $params = []) {
    $this->orderBy = ['field' => $field, 'order' => $order];
  }

}
