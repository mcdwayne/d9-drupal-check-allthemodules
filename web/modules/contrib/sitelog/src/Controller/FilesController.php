<?php

namespace Drupal\sitelog\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;

class FilesController extends ControllerBase {
  public function render() {

    // query data
    $connection = \Drupal::database();
    $result = $connection
      ->select('sitelog_files', 's')
      ->fields('s')
      ->execute();

    // push onto array
    $rows = array();
    foreach ($result as $row) {
      $rows[] = array(
        'logged' => $row->logged,
        'uploaded' => $row->uploaded,
        'storage' => $row->storage,
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
          'sitelog/sitelog.files',
        ),
        'drupalSettings' => array(
          'sitelog' => array(
            'files' => array(
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
    $page[] = \Drupal::formBuilder()->getForm('Drupal\sitelog\Form\FilesForm');
    $page[] = array(
      '#markup' => '</div></div>',
    );

    // add more information link
    $text = t('Files');
    $url = Url::fromRoute('view.files.page_1');
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
