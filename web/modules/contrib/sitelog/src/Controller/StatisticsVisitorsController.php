<?php

namespace Drupal\sitelog\Controller;

use Drupal\Core\Controller\ControllerBase;

class StatisticsVisitorsController extends ControllerBase {
  public function render() {

    // query data
    $connection = \Drupal::database();
    $query = $connection->select('sitelog_access', 's');
    $query->fields('s', array('country'));
    $query->addExpression('count(country)', 'visitors');
    $query->groupBy("country");
    $result = $query->execute()->fetchAllKeyed();

    // get oldest log
    $query = $connection->select('sitelog_access', 's')
      ->fields('s', array('logged'))
      ->orderBy('logged')
      ->range(0, 1);
    $oldest = $query->execute()->fetchField();
    if ($oldest) {
      $date = \Drupal::service('date.formatter')
        ->format($oldest, 'custom', 'd F Y');
      $from = t(' (from ' . $date . ')');
    } else {
      $from = "";
    }

    // encode into json
    $data = json_encode($result);

    // render choropleth
    $page[] = array(
      '#type' => 'inline_template',
      '#template' => '<svg width="1200" height="522.15"></svg>',
      '#attached' => array(
        'library' =>  array(
          'sitelog/sitelog.visitors',
        ),
        'drupalSettings' => array(
          'sitelog' => array(
            'visitors' => array(
              'geometry' => $GLOBALS['base_root'] . base_path() . drupal_get_path('module', 'sitelog') . '/js/world.topo.json',
              'data' => $data,
            ),
          ),
        ),
      ),
      '#prefix' => '<div class="sitelog-container"><div><h2 class="sitelog-title sitelog-center">Visitors' . $from . '</h2>',
      '#suffix' => '</div>',
    );
    return $page;
  }
}
