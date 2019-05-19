<?php

namespace Drupal\sitelog\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;

class SystemEventsController extends ControllerBase {
  public function render() {

    // query data
    $connection = \Drupal::database();
    $result = $connection
      ->select('sitelog_system_events', 's')
      ->fields('s')
      ->execute();

    // push onto array
    $rows = array();
    foreach ($result as $row) {
      $rows[] = array(
        'logged' => $row->logged,
        'emergency' => $row->emergency,
        'alert' => $row->alert,
        'critical' => $row->critical,
        'error' => $row->error,
        'warning' => $row->warning,
        'notice' => $row->notice,
        'info' => $row->info,
        'debug' => $row->debug,
      );
    }

    // encode into json
    $data = json_encode($rows);

    // render chart
    $page[] = array(
      '#type' => 'inline_template',
      '#template' => '<svg width="800" height="400"></svg>',
      '#attached' => array(
        'library' =>  array(
          'sitelog/sitelog.system-events',
        ),
        'drupalSettings' => array(
          'sitelog' => array(
            'system_events' => array(
              'data' => $data,
            ),
          ),
        ),
      ),
      '#prefix' => '<div class="sitelog-container">',
    );

    // render toggle
    $page[] = array(
      '#markup' => '<div class="sitelog-toggles">',
    );
    $page[] = \Drupal::formBuilder()->getForm('Drupal\sitelog\Form\PeriodForm');
    $page[] = array(
      '#markup' => '</div></div>',
    );

    // render legend
    $page[] = array(
      '#markup' => '<div class="sitelog-legend-container"></div>',
    );

    // add more information links
    $links = array(
      array(
        'text' => t('Recent log messages'),
        'route' => 'dblog.overview',
      ),
      array(
        'text' => t('Top \'access denied\' errors'),
        'route' => 'dblog.access_denied',
      ),
      array(
        'text' => t('Top \'page not found\' errors'),
        'route' => 'dblog.page_not_found',
      ),
    );
    foreach ($links as $link) {
      $text = $link['text'];
      $url = Url::fromRoute($link['route']);
      $items[] = Link::fromTextAndUrl($text, $url)->toString();
    }
    $page[] = array(
      '#theme' => 'item_list',
      '#title' => t('More information'),
      '#items' => $items,
    );
    return $page;
  }
}
