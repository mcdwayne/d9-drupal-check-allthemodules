<?php

/**
 * @file
 * Contains \Drupal\restrict_abusive_words\Controller\RestrictWordsController.
 */

namespace Drupal\restrict_abusive_words\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\Query;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Drupal\Core\Database\Query\TableSortExtender;
use Drupal\Core\Url;

class RestrictWordsController extends ControllerBase {
  public function content() {
    $rows = array();

    $header = array(
      array(
        'data' => $this->t('Id'),
        'field' => 'raw.id'
      ),
      array(
        'data' => $this->t('Word'),
        'field' => 'raw.words',
        'sort' => 'desc'
      ),
      array(
        'data' => $this->t('Edit')
      ),
      array(
        'data' => $this->t('Delete')
      )
    );
    $conn = Database::getConnection();
    $query = $conn->select('restrict_abusive_words', 'raw')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('Drupal\Core\Database\Query\TableSortExtender');
    $query->fields('raw');

    $result = $query
      ->limit(10)
      ->orderByHeader($header)
      ->execute();

    foreach ($result as $word) {
      $editUrl = Url::fromRoute('restrict_abusive_words.edit_words', array('wid' => $word->id));
      $deleteUrl = Url::fromRoute('restrict_abusive_words.delete_words', array('wid' => $word->id));
      $rows[] = array(
        'data' => array(
          array('data' => $word->id),
          array('data' => $word->words),
          array('data' => \Drupal::l(t('Edit word'), $editUrl)),
          array('data' => \Drupal::l(t('Delete word'), $deleteUrl))
        )
      );
    }

    $build['result'] = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No words added.'),
    );
    $build['pager'] = array('#type' => 'pager');

    return $build;
  }
}

