<?php

/**
 * Contains \Drupal\publishthis\Controller\AdvertisementController.
 */

namespace Drupal\publishthis\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

class PublishthisActionController extends ControllerBase {

  public function getPublishthisAction() {
    return [
      '#theme' => 'publishthis_action',
      '#data' => $this->getPublishthisActionData(),
    ];
  }

  private function getPublishthisActionData() {
    $header = [
      ['data' => $this->t('Title'), 'field' => 'title'],
      ['data' => $this->t('Content Type Format'), 'field' => 'format_type'],
      ['data' => $this->t('Content Type'), 'field' => 'name'],
      'action' => ['data' => $this->t('Action')],
    ];

    $result = \Drupal::database()->select('pt_publishactions', 'pb')
      ->fields('pb', [])
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->limit(10)
      ->extend('Drupal\Core\Database\Query\TableSortExtender')
      ->orderByHeader($header)
      ->execute();

    $rows = [];
    foreach ($result as $row) {
      $links['edit'] = [
        'title' => $this->t('Edit Action'),
        'url' => Url::fromRoute('publishthis.publishthis-action-edit', ['id' => $row->id]),
      ];

      $links['delete'] = [
        'title' => $this->t('Delete Action'),
        'url' => Url::fromRoute('publishthis.publishthis-action-delete', ['id' => $row->id]),
      ];

      $operations['data'] = [
        '#type' => 'operations',
        '#links' => $links,
      ];

      $rows[$row->id] = [
        $row->title,
        $row->format_type,
        $row->name,
        $operations,
      ];
    }
    $output['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];

    $output['pager'] = [
      '#type' => 'pager',
      '#route_name' => 'publishthis.publishthis-action',
    ];
    
    return $output;
  }
}