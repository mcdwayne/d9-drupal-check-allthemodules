<?php

namespace Drupal\customfieldtoken\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Provides route responses for the Example module.
 */
class TokenListing extends ControllerBase {

  /**
   * Returns a simple page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function token_list() {
    $header = [

      $this->t('Token Name'),

      $this->t('Max length for token replacement data'),
      [
        'data' => $this->t('Operation'),
        'colspan' => '3',
      ],
    ];
    $query = db_select('custom_token', 'ct')
      ->fields('ct', ['field_machine_name', 'rid', 'max_trim_length'])
      ->execute()
      ->fetchAll();
    $table_field_values = [];
    $size = 0;
    foreach ($query as $value) {
      $table_field_values[$value->rid] = $value;
      $size++;
    }
    $size = 0;
    foreach ($query as $value) {
      $table_field_values[$value->field_machine_name] = $value;
      $size++;
    }
    $size = 0;
    foreach ($query as $value) {
      $table_field_values[$value->max_trim_length] = $value;
      $size++;
    }
    $s = 1;
    foreach ($table_field_values as $table_field_value) {
      $rid[$s] = $table_field_value->rid;
      $fmn[$s] = $table_field_value->field_machine_name;
      $mtl[$s] = $table_field_value->max_trim_length;
      $s++;
    }
    $s = 1;
    $rows = [];
    $custom = t('this');
    for ($n = 1; $n <= $size; $n++) {

      $rows[] = [
        'data' => [

          $this->t('[custom_token:' . $fmn[$n] . ']'),
          $this->t($mtl[$n]),
          \Drupal::l($this->t('Edit'), Url::fromUri('internal:/admin/custom-token/' . $rid[$n] . '/edit')),
          \Drupal::l($this->t('Delete'), Url::fromUri('internal:/admin/custom-token/' . $rid[$n] . '/delete')),

        ],
      ];

    }
    $build['custom_token_listing'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('no custom tokens created yet'),
    ];
    $build['admin_cleantaxonomy_list_pager'] = ['#theme' => 'pager'];
    return $build;
  }

}
