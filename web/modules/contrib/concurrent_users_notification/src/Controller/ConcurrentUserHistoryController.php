<?php

namespace Drupal\concurrent_users_notification\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for tablesort example routes.
 */
class ConcurrentUserHistoryController extends ControllerBase {

  /**
   * The Database Connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('database')
    );
  }

  /**
   * TableSortExampleController constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *    The databse connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * A simple controller method to explain what the tablesort example is about.
   */
  public function showHistory() {
    $header = array(
      array('data' => 'ID', 'field' => 't.item_id'),
      array('data' => 'Date', 'field' => 't.concurrent_logins_date'),
      array('data' => 'Concurrent User Count (MAX)', 'field' => 't.concurrent_logins_count'),
    );

    // Using the TableSort Extender is what tells  the query object that we
    // are sorting.
    $query = $this->database->select('concurrent_users_notification', 't')
        ->extend('Drupal\Core\Database\Query\TableSortExtender');
    $query->fields('t');

    // Don't forget to tell the query object how to find the header information.
    $result = $query
        ->orderByHeader($header)
        ->execute();

    $rows = array();
    foreach ($result as $row) {
      $rows[] = array('data' => (array) $row);
    }

    // Build the table for the nice output.
    $build = array(
      '#markup' => '<h1>' . 'Concurrent logged in user count history' . '</h1>',
    );
    $build['clear_history_burron'] = \Drupal::formBuilder()->getForm('Drupal\concurrent_users_notification\Form\ClearHistoryForm');

    $build['tablesort_table'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => ['id' => 'cuncurrent-user-history-table-wrapper'],
      '#empty' => 'No entries available.',
    );

    // Don't cache this page.
    $build['#cache']['max-age'] = 0;

    return $build;
  }

}
