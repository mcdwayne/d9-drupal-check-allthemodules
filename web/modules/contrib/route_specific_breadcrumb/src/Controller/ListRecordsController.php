<?php

namespace Drupal\route_specific_breadcrumb\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Link;

/**
 * Class ListRecordsController.
 */
class ListRecordsController extends ControllerBase {

  protected $database;

  /**
   * {@inheritdoc}
   *
   * @paramObject $database
   *   The database connection.
   */
  public function __construct($database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('database')
    );
  }

  /**
   * Get Tables.
   *
   * @return string
   *   Return array.
   */
  public function getRoute() {
    $data = $this->database->select('route_specific_breadcrumb', 'r');
    $data->fields('r', [
      'uid',
      'route',
      'description',
      'created',
      'updated',
      'rid',
    ]
    );
    $rows = [];
    $header = array(
      'ID',
      'Route',
      'Description',
      'Created',
      'Updated',
      'Edit',
    );
    $table_sort = $data->extend('Drupal\Core\Database\Query\TableSortExtender')->orderByHeader($header);
    $pager = $table_sort->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(10);
    $result = $pager->execute();
    $result->allowRowCount = TRUE;
    if ($result->rowCount() > 0) {
      foreach ($result as $row) {
        $row->created = date('d-m-Y H:i:s', $row->created);
        $row->updated = date('d-m-Y H:i:s', $row->updated);
        // Internal path (defined by a route in Drupal 8).
        $internal_link = Link::createFromRoute('edit', 'route_specific_breadcrumb.route_specific_form', [
          'rid' => $row->rid,
        ]
        );
        $row = (array) $row;
        $row['rid'] = $internal_link;
        $rows[] = array('data' => (array) $row, 'style' => 'word-break:break-all;');
      }
    }
    $build = [
      '#markup' => 'List of all data',
    ];

    $build['location_table'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => 'No items available',
    ];
    $build['pager'] = [
      '#type' => 'pager',
    ];
    return $build;
  }

  /**
   * Function routeCheck.
   *
   * @return string
   *   Return check.
   */
  static public function routeCheck($obj, $route) {
    // Return TRUE if $sendData is FALSE
    // Else Return $value object.
    $data = $obj->select('route_specific_breadcrumb', 'r')
      ->fields('r', ['route', 'description'])
      ->condition('r.route', $route, '=')
      ->execute();
    $data->allowRowCount = TRUE;
    if ($data->rowCount() > 0) {
      foreach ($data as $value) {
        return $value;
      }
    }
    return FALSE;
  }

  /**
   * routeData.
   *
   * @return string
   *   Return check.
   */
  static public function routeData($obj, $rid) {
    // Return TRUE if $sendData is FALSE
    // Else Return $value object.
    $data = $obj->select('route_specific_breadcrumb', 'r')
      ->fields('r', ['route', 'description'])
      ->condition('r.rid', $rid, '=')
      ->execute();
    $data->allowRowCount = TRUE;
    if ($data->rowCount() > 0) {
      foreach ($data as $value) {
        return $value;
      }
    }
    return FALSE;
  }

  /**
   * Routedelete.
   *
   * @return bool
   *   Return TRUE if data is deleted or FALSE otherwise.
   */
  static public function routeDelete($obj, $rid) {
    return $obj->delete('route_specific_breadcrumb')
      ->condition('rid', $rid, '=')
      ->execute() === NULL ? FALSE : TRUE;
  }

}
