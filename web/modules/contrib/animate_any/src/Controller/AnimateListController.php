<?php

/**
 * @file
 * Contains \Drupal\animate_any\Controller\AnimateListController.
 */

namespace Drupal\animate_any\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

class AnimateListController extends ControllerBase {

  public function animate_list() {
    $header = $rows = [];
    $header[] = ['data' => $this->t('ID')];
    $header[] = ['data' => $this->t('Parent element')];
    $header[] = ['data' => $this->t('Identifiers')];
    $header[] = ['data' => $this->t('Operation')];

    // Fetch Animate Data.
    $fetch = \Drupal::database()->select("animate_any_settings", "a");
    $fetch->fields('a');
    $fetch->orderBy('aid', 'DESC');
    $table_sort = $fetch->extend('Drupal\Core\Database\Query\TableSortExtender')->orderByHeader($header);
    $pager = $table_sort->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(10);
    $fetch_results = $pager->execute()->fetchAll();
    foreach ($fetch_results as $items) {
      $mini_header = [];
      $mini_header[] = ['data' => $this->t('Section')];
      $mini_header[] = ['data' => $this->t('Animation')];
      $mini_rows = [];
      $data = \json_decode($items->identifier);
      foreach ($data as $value) {
        $mini_rows[] = [$value->section_identity, $value->section_animation];
      }
      $mini_output = [];
      $mini_output['mini_list'] = [
        '#theme' => 'table',
        '#header' => $mini_header,
        '#rows' => $mini_rows,
      ];

      $identifiers = drupal_render($mini_output);

      $links = [];

      $links['edit'] = [
        'title' => $this->t('Edit'),
        'url' => Url::fromUri('internal:/admin/config/animate_any/edit/' . $items->aid, ['query' => ['destination' => 'admin/config/animate_any/list']]),
      ];

      $links['delete'] = [
        'title' => $this->t('Delete'),
        'url' => Url::fromUri('internal:/admin/config/animate_any/delete/' . $items->aid, ['query' => ['destination' => 'admin/config/animate_any/list']]),
      ];

      $operation = [
        'data' => [
          '#type' => 'operations',
          '#links' => $links,
        ],
      ];

      $rows[] = [
        $items->aid, $items->parent, $identifiers, $operation,
      ];
    }
    $add = \Drupal::l($this->t('Add Animation'), Url::fromUri('internal:/admin/config/animate_any', ['attributes' => ['class' => ['button']]]));
    $add_link = '<ul class="action-links"><li>' . $add . '</li></ul>';

    $empty = '<div role="contentinfo" aria-label="Status message" class="messages messages--warning">No record found.</div>';

    $output['animate_list'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t($empty),
      '#prefix' => $add_link,
    ];
    $output['pager'] = [
      '#type' => 'pager'
    ];
    return $output;
  }

}
