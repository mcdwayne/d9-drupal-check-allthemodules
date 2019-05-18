<?php

namespace Drupal\intelligent_tools_duplicate_rate\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;

/**
 *
 */
class IntelliController2 extends ControllerBase {

  /**
   *
   */
  public function adminOverview(Request $request) {
    $header = [];
    $header[] = ['data' => $this->t('Content Type'), 'field' => 'alias', 'sort' => 'asc'];
    $header[] = ['data' => $this->t('Extraction Field'), 'field' => 'source'];
    $header[] = $this->t('Operations');
    $config = \Drupal::config('intelligent_tools.settings');
    $ip_display = $config->get('intelligent_tools_duplicate_rate_ip');
    $content_type_node = $config->get('intelligent_tools_duplicate_rate_content');
    $content_type_node = strtolower($content_type_node);
    $content_type_node_array = explode(" ", $content_type_node);
    $content_type_field = $config->get('intelligent_tools_duplicate_rate_field');
    $content_type_field_array = explode(" ", $content_type_field);
    for ($j = 0; $j < sizeof($content_type_node_array); $j++) {
      $some_array[$j] = [$content_type_node_array[$j], $content_type_field_array[$j]];
    }
    $rows = [];
    $destination = $this->getDestinationArray();
    foreach ($some_array as $data) {
      $row = [];
      if ($data[0] == '') {
        break;
      }
      $row['data']['alias'] = $data[0];
      $row['data']['source'] = $data[1];
      $string_data = implode("###", $data);
      $operations = [];
      $operations['edit'] = [
        'title' => $this->t('Edit'),
        'url' => Url::fromRoute('intelligent_tools_duplicate_rate_form.settings', ['pid' => $string_data]),
      ];
      $operations['delete'] = [
        'title' => $this->t('Delete'),
        'url' => Url::fromRoute('intelligent_tools_duplicate_rate_del.settings', ['pid' => $string_data]),
      ];
      $row['data']['operations'] = [
        'data' => [
          '#type' => 'operations',
          '#links' => $operations,
        ],
      ];
      $rows[] = $row;
    }
    $build['ip'] = [
      '#markup' => '<div>' . t('The Current web address is: ') . $ip_display . '<br>' . '</div>',
    ];
    $build['path_table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No settings available. <a href=":link">Add Settings</a>.', [':link' => $this->url('intelligent_tools_duplicate_rate_addone.settings')]),
    ];
    $build['path_pager'] = ['#type' => 'pager'];

    return $build;
  }

}
