<?php

namespace Drupal\sitelog\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;

class SearchesController extends ControllerBase {

  public function render() {

    // query data
    $connection = \Drupal::database();
    $query = $connection->select('sitelog_searches', 's');
    $query->fields('s', array('term'));
    $query->condition('logged', strtotime('-1 year'), '>');
    $query->addExpression('sum(searches)', 'searches');
    $query->groupBy('term');
    $query->orderBy('searches', 'DESC');
    $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->limit(5);
    $result = $pager->execute()->fetchAllKeyed(0, 1);

    // encode into json
    $data = json_encode($result);

    // render chart
    $page[] = array(
      '#type' => 'inline_template',
      '#template' => '<svg width="700" height="400"></svg>',
      '#attached' => array(
        'library' =>  array(
          'sitelog/sitelog.searches',
        ),
        'drupalSettings' => array(
          'sitelog' => array(
            'searches' => array(
              'data' => $data,
            ),
          ),
        ),
      ),
      '#prefix' => '<div class="sitelog-container">',
    );

    // generate rows
    $rows = array();
    foreach ($result as $key => $value) {
      $rows[] = array(
        array('data' => $key),
        array('data' => $value),
      );
    }

    // render table
    $page[] = array(
      '#type' => 'table',
      '#caption' => t('All searches, over past 12 months'),
      '#header' => array(t('Term'), t('Searches')),
      '#rows' => $rows,
      '#empty' => t('None'),
      '#prefix' => '<div>',
    );
    $page[] = array('#type' => 'pager', '#suffix' => '</div></div>');

    // add more information link
    $text = t('Top search phrases');
    $url = Url::fromRoute('dblog.search');
    $link = Link::fromTextAndUrl($text, $url)->toString();
    $items = array($link);
    $page[] = array(
      '#theme' => 'item_list',
      '#title' => t('More information'),
      '#items' => $items,
    );
    return $page;
  }
}
