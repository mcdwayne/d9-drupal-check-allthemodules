<?php

namespace Drupal\uc_stock\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\uc_report\Controller\Reports;

/**
 * Displays a stock report for products with stock tracking enabled.
 */
class StockReports extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function report() {

    //$page_size = (isset($_GET['nopage'])) ? UC_REPORT_MAX_RECORDS : variable_get('uc_report_table_size', 30);
    $page_size = 30;
    $csv_rows = [];
    $rows = [];

    $header = [
      ['data' => $this->t('SKU'), 'field' => 'sku', 'sort' => 'asc'],
      ['data' => $this->t('Product'), 'field' => 'title'],
      ['data' => $this->t('Stock'), 'field' => 'stock'],
      ['data' => $this->t('Threshold'), 'field' => 'threshold'],
      ['data' => $this->t('Operations')],
    ];

    $csv_rows[] = [$this->t('SKU'), $this->t('Product'), $this->t('Stock'), $this->t('Threshold')];

    $query = db_select('uc_product_stock', 's')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('\Drupal\Core\Database\Query\TableSortExtender')
      ->orderByHeader($header)
      ->limit($page_size)
      ->fields('s', [
        'nid',
        'sku',
        'stock',
        'threshold',
      ]);

    $query->leftJoin('node_field_data', 'n', 's.nid = n.nid');
    $query->addField('n', 'title');
    $query->condition('active', 1)
      ->condition('title', '', '<>');

    // @todo Replace arg().
    // if (arg(4) == 'threshold') {
    //   $query->where('threshold >= stock');
    // }

    $result = $query->execute();
    foreach ($result as $stock) {
      $op = '';
      if ($this->currentUser()->hasPermission('administer product stock')) {
        $op = [
          '#type' => 'operations',
          '#links' => [
            'edit' => [
              'title' => $this->t('Edit'),
              'url' => Url::fromRoute('uc_stock.edit', ['node' => $stock->nid], ['query' => ['destination' => 'admin/store/reports/stock']]),
            ],
          ],
        ];
      }

      // Add the data to a table row for display.
      $rows[] = [
        'data' => [
          ['data' => $stock->sku],
          ['data' => ['#type' => 'link', '#title' => $stock->title, '#url' => Url::fromRoute('entity.node.canonical', ['node' => $stock->nid])]],
          ['data' => $stock->stock],
          ['data' => $stock->threshold],
          ['data' => $op],
        ],
        'class' => [($stock->threshold >= $stock->stock) ? 'uc-stock-below-threshold' : 'uc-stock-above-threshold'],
      ];

      // Add the data to the CSV contents for export.
      $csv_rows[] = [$stock->sku, $stock->title, $stock->stock, $stock->threshold];
    }

    // Cache the CSV export.
    $controller = new Reports();
    $csv_data = $controller->store_csv('uc_stock', $csv_rows);

    $build['form'] = $this->formBuilder()->getForm('\Drupal\uc_stock\Form\StockReportForm');
    $build['report'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => ['width' => '100%', 'class' => ['uc-stock-table']],
    ];
    $build['pager'] = [
      '#type' => 'pager',
    ];

    $build['links'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['uc-reports-links']],
    ];
    $build['links']['export_csv'] = [
      '#type' => 'link',
      '#title' => $this->t('Export to CSV file'),
      '#url' => Url::fromRoute('uc_report.getcsv', ['report_id' => $csv_data['report'], 'user_id' => $csv_data['user']]),
      '#suffix' => '&nbsp;&nbsp;&nbsp;',
    ];

//    if (isset($_GET['nopage'])) {
//      $build['links']['toggle_pager'] = [
//        '#type' => 'link',
//        '#title' => $this->t('Show paged records'),
//        '#url' => Url::fromRoute('uc_stock.reports'),
//      ];
//    }
//    else {
      $build['links']['toggle_pager'] = [
        '#type' => 'link',
        '#title' => $this->t('Show all records'),
        '#url' => Url::fromRoute('uc_stock.reports', [], ['query' => ['nopage' => '1']]),
      ];
//    }

    return $build;
  }

}
