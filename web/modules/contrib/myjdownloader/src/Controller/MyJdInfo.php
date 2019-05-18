<?php

namespace Drupal\myjdownloader\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\myjdownloader\MyJdHelper;

/**
 * Info displays.
 */
class MyJdInfo extends ControllerBase {

  /**
   * Get JD state.
   */
  public function getState() {

    $output = [];
    $output['#cache']['max-age'] = 0;

    $list = MyJdHelper::getState();

    if (empty($list)) {
      $output[] = [
        '#type' => 'markup',
        '#markup' => "No data, Check the jDownloader settings and connection.",
        '#prefix' => '<div><b><i>',
        '#suffix' => '</i></b></div>',
      ];
    }
    else {

      foreach ($list as $keys => $items) {
        $count = count($items);
        $table = [
          '#theme' => 'table',
          '#cache' => ['max-age' => 0],
          '#caption' => "$keys ($count)",
          '#header' => ['Name', 'Value'],
          '#rows' => [],
        ];

        foreach ($items as $key => $value) {
          $table['#rows'][] = [$key, $value];
        }

        $output['tables'][] = $table;
      }
    }

    return $output;
  }

}
