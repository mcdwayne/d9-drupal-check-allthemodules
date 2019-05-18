<?php

namespace Drupal\seasonal_product_recommendations\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;

/**
 * Class DisplayHemisphere.
 *
 * @package Drupal\seasonal_product_recommendations\Controller
 */
class DisplayHemisphere extends ControllerBase {

  /**
   * Returns the table.
   *
   * @returns $build.
   */
  public function display() {
    $header = [
      ['data' => t('Hemisphere'), 'field' => 'hemisphere'],
      ['data' => t('Season'), 'field' => 'season'],
      ['data' => t('Start date'), 'field' => 'start_date'],
      ['data' => t('End date'), 'field' => 'end_date'],
      ['data' => t('Operations'), 'colspan' => 2],
    ];

    // Select records from table.
    $query = \Drupal::database()->select('hemisphere_seasons', 'hs');
    $query->fields('hs');
    $table_sort = $query->extend('Drupal\Core\Database\Query\TableSortExtender')->orderByHeader($header);
    $pager = $table_sort->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(10);
    $results = $pager->execute()->fetchAll();

    foreach ($results as $data) {
      $delete = Url::fromUserInput('/seasonal_product_recommendations/form/delete/' . $data->hid);
      $edit = Url::fromUserInput('/admin/config/seasonal_product_recommendations/seasons/configure/edit/' . $data->hid);
      $tid = $data->season;
      $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($tid);
      $title = $term->name->value;
      // Print the data from table.
      $row = [
        'hemisphere' => $data->hemisphere,
        'season' => $title,
        'start_date' => $data->start_date,
        'end_date' => $data->end_date,
        'edit' => \Drupal::l('Edit', $edit),
        'delete' => \Drupal::l('Delete', $delete),
      ];
      $rows[] = ['data' => (array) $row];
    }

    $build = ['#markup' => t('List of all the seasons with their durations.')];

    // Table header will be sticky.
    $build['location_table'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => isset($rows) ? $rows : '',
      // The message to be displayed if table is empty.
      "#empty" => t("Table has no row!"),
    ];
    $build['pager'] = ['#type' => 'pager'];

    return $build;
  }

}
