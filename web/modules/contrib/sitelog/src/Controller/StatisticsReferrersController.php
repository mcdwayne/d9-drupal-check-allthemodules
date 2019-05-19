<?php

namespace Drupal\sitelog\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Link;

class StatisticsReferrersController extends ControllerBase {
  public function render() {

    // generate yesterday date/time
    $yesterday = new \DateTime('yesterday');
    $start = $yesterday->getTimestamp();
    $end = $yesterday->setTime(23, 59, 59)->getTimestamp();

    // query data
    $connection = \Drupal::database();
    $query = $connection->select('sitelog_access', 's')
      ->fields('s', array('url'))
      ->condition('logged', array($start, $end), 'BETWEEN');
    $query->addExpression('count(url)', 'count');
    $query->groupBy('url');
    $query->orderBy('count', 'DESC');
    $query->range(0, 5);
    $result = $query->execute();

    // generate rows
    $rows = array();
    $total = 0;
    foreach ($result as $row) {
      if ($row->count) {

        // push on data
        $rows[] = array(
          'url' => $row->url,
          'count' => $row->count,
        );

        // increment total
        $total += $row->count;
      }
    }

    // add others total
    $query = $connection->select('sitelog_access', 's')
      ->condition('logged', array($start, $end), 'BETWEEN');
    $result = $query->countQuery()->execute()->fetchField();

    // generate rows
    if ($result) {
      $rows[] = array(
        'title' => t('Others'),
        'count' => $result - $total,
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
          'sitelog/sitelog.referrers',
        ),
        'drupalSettings' => array(
          'sitelog' => array(
            'referrers' => array(
              'data' => $data,
            ),
          ),
        ),
      ),
      '#prefix' => t('<div class="sitelog-container"><div><h2 class="sitelog-title sitelog-center">Top referrers yesterday</h2>'),
      '#suffix' => '</div>',
    );

    // query data
    $query = $connection->select('sitelog_access', 's')
      ->fields('s', array('url'));
    $query->addExpression('count(url)', 'count');
    $query->groupBy('url');
    $query->orderBy('count', 'DESC');
    $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->limit(10);
    $result = $pager->execute();

    // generate rows
    $rows = array();
    foreach ($result as $data) {

      // generate link
      $link = Link::fromTextAndUrl($data->url, Url::fromUri($data->url, array()))->toString();

      // push on data row
      $rows[] = array(
        array('data' => $link),
        array('data' => $data->count),
      );
    }

    // get oldest log
    $query = $connection->select('sitelog_access', 's')
      ->fields('s', array('logged'))
      ->orderBy('logged')
      ->range(0, 1);
    $result = $query->execute()->fetchField();
    if ($result) {
      $date = \Drupal::service('date.formatter')
        ->format($query->execute()->fetchField(), 'custom', 'd F Y');
      $from = t('(from ' . $date . ')');
    } else {
      $from = "";
    }

    // render table
    $page[] = array(
      '#type' => 'table',
      '#caption' => t('History @from', array(
        '@from' => $from,
      )),
      '#header' => array(t('URL'), t('Referrals')),
      '#rows' => $rows,
      '#empty' => t('None'),
      '#prefix' => '<div>',
    );
    $page[] = array('#type' => 'pager', '#suffix' => '</div></div>');
    return $page;
  }
}
