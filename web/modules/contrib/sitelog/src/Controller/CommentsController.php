<?php

namespace Drupal\sitelog\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;

class CommentsController extends ControllerBase {
  public function render() {

    // query data
    $connection = \Drupal::database();
    $result = $connection
      ->select('sitelog_comments', 's')
      ->fields('s')
      ->execute();

    // push onto array
    $rows = array();
    foreach ($result as $row) {
      $rows[] = array(
        'logged' => $row->logged,
        'posted' => $row->posted,
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
          'sitelog/sitelog.comments',
        ),
        'drupalSettings' => array(
          'sitelog' => array(
            'comments' => array(
              'data' => $data,
            ),
          ),
        ),
      ),
      '#prefix' => '<div class="sitelog-container">',
    );

    // render toggles
    $page[] = array(
      '#markup' => '<div class="sitelog-toggles">',
    );
    $page[] = \Drupal::formBuilder()->getForm('Drupal\sitelog\Form\PeriodForm');
    $page[] = array(
      '#markup' => '</div></div>',
    );

    // add legend
    $page[] = array(
      '#theme' => 'image',
      '#uri' => drupal_get_path('module', 'sitelog') . '/img/purples.png',
      '#alt' => "",
      '#width' => 150,
      '#height' => 12,
      '#prefix' => '<div class="sitelog-color-scheme"><span>01 Jan</span>',
      '#suffix' => '<span>31 Dec</span></div>',
    );

    // add more information link
    $text = t('Conmments');
    $url = Url::fromRoute('comment.admin');
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
