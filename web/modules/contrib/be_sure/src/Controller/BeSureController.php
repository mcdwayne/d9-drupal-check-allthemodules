<?php

namespace Drupal\be_sure\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Class BeSureController.
 *
 * @package Drupal\be_sure\Controller
 */
class BeSureController extends ControllerBase {
  /**
   * Main.
   *
   * @return string
   *   Return Hello string.
   */
  public function main() {
    $info = be_sure_get_info();
    $contents = [];

    foreach ($info as $name => $item) {
      $total_passed = $total_passed_max = 0;
      foreach ($item['elements'] as $sub_item) {
        list(, $passed) = be_sure_proceed_elements($sub_item['items']);
        $total_passed += $passed;
        $total_passed_max += count($sub_item['items']);
      }
      $total_percentage = round(($total_passed / $total_passed_max) * 100);
      $progress_bar = [
        '#theme' => 'be_sure_progress_bar',
        '#percent' => $total_percentage,
        '#message' => t('Total info about @name: @passed/@total issues resolved', [
          '@passed' => $total_passed,
          '@total' => $total_passed_max,
          '@name' => $name,
        ]),
        '#attributes' => [
          'class' => [
            $total_percentage > 25 ? ($total_percentage > 75 ? 'bes-success' : 'bes-normal') : 'bes-warning',
            'progress__bar',
            'filled',
          ],
          'style' => [
            'width' => "{$total_percentage}%",
          ]
        ],
      ];
      $contents[] = [
        'title' => t($item['title']),
        'description' => t($item['description']) . render($progress_bar),
        'url' => Url::fromRoute('be_sure.module_page', ['module_name' => $name]),
        'localized_options' => [],
      ];
    }

    if ($contents) {
      $output = [
        '#theme' => 'admin_block_content',
        '#content' => $contents,
        '#attached' => [
          'library' => ['be_sure/be_sure.main'],
        ],
      ];
    }
    else {
      $url = Url::fromRoute('system.modules_list', [], ['fragment' => 'module-be-sure']);
      $project_link = Link::fromTextAndUrl(t('here'), $url);
      $project_link = $project_link->toRenderable();

      $text = t('Seems you are not enabled default submodules to see the status of SEO/Security/Performance. You can enable their %here.', ['%here' => render($project_link)]);

      $output = [
        '#markup' => render($text),
      ];
    }

    return $output;
  }
  /**
   * Module_info.
   *
   * @return string
   *   Return Hello string.
   */
  public function module_info($module_name) {
    $info = be_sure_get_info();

    $item = $info[$module_name];
    $titles = [];
    $results = [];
    if (count($item['elements']) > 1) {
      foreach ($item['elements'] as $element) {
        $id = uniqid('be-sure-');
        $titles[$id] = t($element['title']);

        list($result, $passed) = be_sure_proceed_elements($element['items']);
        $percent = round($passed / count($element['items']) * 100);

        $results[$id] = [
          '#theme' => 'be_sure_element',
          '#items' => $result,
          '#status' => $percent,
          '#passed' => $passed,
        ];
      }

      $output = [
        '#theme' => 'be_sure_multiple',
        '#titles' => $titles,
        '#elements' => $results,
        '#attached' => [
          'library' => ['be_sure/be_sure.main'],
        ],
      ];
    }
    else {
      list($result, $passed) = be_sure_proceed_elements($item['elements'][0]['items']);
      $percent = round($passed / count($item['elements'][0]['items']) * 100);

      $output = [
        '#theme' => 'be_sure_element',
        '#items' => $result,
        '#status' => $percent,
        '#passed' => $passed,
        '#attached' => [
          'library' => ['be_sure/be_sure.main'],
        ],
      ];
    }

    return $output;
  }

}
