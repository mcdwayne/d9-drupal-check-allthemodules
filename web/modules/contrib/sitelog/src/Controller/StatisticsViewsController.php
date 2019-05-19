<?php

namespace Drupal\sitelog\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Link;

class StatisticsViewsController extends ControllerBase {
  public function render() {

    // query data
    $connection = \Drupal::database();
    $result = $connection
      ->select('node_counter', 'n')
      ->fields('n', array('nid', 'daycount'))
      ->orderBy('daycount', 'DESC')
      ->orderBy('timestamp', 'DESC')
      ->range(0, 5)
      ->execute();

    // generate rows
    $rows = array();
    $total = 0;
    foreach ($result as $row) {
      if ($row->daycount) {

        // get node title
        $title = \Drupal::entityTypeManager()
          ->getStorage('node')
          ->load($row->nid)
          ->getTitle();

        // push on data
        $rows[] = array(
          'title' => $title,
          'daycount' => $row->daycount,
        );

        // increment total
        $total += $row->daycount;
      }
    }

    // add others total
    $query = $connection->select('node_counter', 'n');
    $query->addExpression('sum(daycount)');
    $result = $query->execute()->fetchField();
    if ($result) {
      $rows[] = array(
        'title' => t('Others'),
        'daycount'=> $result - $total,
      );
    }

    // encode into json
    $data = json_encode($rows);

    // render chart
    $page[] = array(
      '#type' => 'inline_template',
      '#template' => '<svg width="500" height="500"></svg>',
      '#attached' => array(
        'library' =>  array(
          'sitelog/sitelog.views',
        ),
        'drupalSettings' => array(
          'sitelog' => array(
            'views' => array(
              'data' => $data,
            ),
          ),
        ),
      ),
      '#prefix' => '<div class="sitelog-container"><div><h2 class="sitelog-title sitelog-center">Most viewed today</h2>',
      '#suffix' => '</div>',
    );

    // query data
    $query = $connection
      ->select('node_counter', 'n')
      ->fields('n')
      ->orderBy('totalcount', 'DESC');
    $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->limit(10);
    $result = $pager->execute();

    // generate rows
    $rows = array();
    foreach ($result as $data) {

      // get node title
      $title = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->load($data->nid)
        ->getTitle();

      // generate link
      $link = Link::fromTextAndUrl($title, Url::fromRoute('entity.node.canonical', ['node' => $data->nid]));

      // format date
      $last_viewed = \Drupal::service('date.formatter')
        ->format($data->timestamp, 'custom', 'd/m/Y');

      // push on data row
      $rows[] = array(
        array('data' => $link),
        array('data' => $data->totalcount),
        array('data' => $last_viewed),
      );
    }

    // render table
    $page[] = array(
      '#type' => 'table',
      '#caption' => t('History'),
      '#header' => array(t('Title'), t('Views'), t('Last viewed')),
      '#rows' => $rows,
      '#empty' => t('None'),
      '#prefix' => '<div>',
    );
    $page[] = array('#type' => 'pager', '#suffix' => '</div></div>');
    return $page;
  }
}
