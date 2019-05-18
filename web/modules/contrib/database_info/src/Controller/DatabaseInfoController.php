<?php

namespace Drupal\database_info\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Menu\MenuTreeParameters;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Menu;

/**
 * A Database controller.
 */
class DatabaseInfoController extends ControllerBase {

  protected $pathObject;
  protected $connection;

  /**
   * {@inheritdoc}
   */
  public function __construct($pathObject, Connection $connection) {
    $this->pathObject = $pathObject;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('path.current'),
      $container->get('database')
    );
  }

  /**
   * Provides the configuration interface for Database.
   */
  public function settings() {
    $output = '';
    $link_id = 'database_info';
    $links = $this->overview($link_id);
    if ($links) {
      $output = '<ul class="admin-list">';
      foreach ($links as $key => $value) {
        $output .= '<li>
                      <a href="' . base_path() . $value . '">' . $key . '</a>
                    </li>';
      }
    }
    $build = [
      '#type' => 'markup',
      '#markup' => $output,
    ];
    $output .= '</ul>';
    return $build;
  }

  /**
   * Provide the administration overview page.
   *
   * @param string $link_id
   *   The ID of the link for which to display child links.
   *
   * @return array
   *   A renderable array of the administration overview page.
   */
  public function overview($link_id = 'database_info') {

    $menu_tree = new Menu();
    $parameters = new MenuTreeParameters();
    $parameters->setRoot($link_id)->excludeRoot()->setTopLevelOnly()->onlyEnabledLinks();
    $tree = $menu_tree->load('admin', $parameters);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $tree = $menu_tree->transform($tree, $manipulators);
    $menu = $menu_tree->build($tree);

    $blocks = [];
    foreach ($menu['#items'] as $item) {
      if ($item['url']->getRouteName() == '') {
        $blocks[$item['title']] = $item['url']->getRouteName();
      }
      else {
        $blocks[$item['title']] = $item['url']->getInternalPath();
      }
    }

    return $blocks;
  }

  /**
   * List all the database table of default Database.
   *
   * @return array
   *   A renderable array of the database table of @table_name.
   */
  public function showDatabase() {

    $current_path = $this->pathObject->getPath();
    $connection = $this->connection;
    $tables = $connection->query('SHOW TABLES');
    $alltables = $tables->fetchAll();
    $output = "";
    $output .= "<div id='list-wrapper'><div>";
    $output .= "<ul class='list-tables'>";
    foreach ($alltables as $value) {
      $val = array_values(get_object_vars($value));
      $output .= '<li class="db-table" data-filter="' . $val[0] . '"><a href="' . base_path() . substr($current_path, 1) . '/' . $val[0] . '">' . $val[0] . '</a></li>';
    }
    $output .= '</ul></div></div>';

    $build = [
      '#type' => 'text',
      '#markup' => $output,
      '#attached' => [
        'library' => [
          'database_info/database_info.admin',
        ],
      ],
    ];

    return $build;
  }

  /**
   * List all the data of table with columns.
   *
   * @param string $table_name
   *   The Database table name for which data to be display.
   *
   * @return array
   *   A renderable array of the database table of @table_name.
   */
  public function showTable($table_name) {

    $connection = $this->connection;
    $fields = $connection->query("DESCRIBE $table_name")->fetchAll();

    $header = [];
    foreach ($fields as $value) {
      $header[$value->Field] = $value->Field;
    }

    $query = $connection->select($table_name);
    $query->fields($table_name);
    $table_sort = $query->extend('Drupal\Core\Database\Query\TableSortExtender')->orderByHeader($header);
    $pager = $table_sort->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(20);
    $results = $pager->execute()->fetchAll();

    $output = [];
    foreach ($results as $result) {
      $output[] = (array) $result;
    }

    $build = [
      '#markup' => 'Database Table : <b>' . $table_name . '</b>',
    ];

    $build['table'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $output,
      '#empty' => $this->t('No Data Found'),
    ];

    $build['pager'] = [
      '#type' => 'pager',
    ];

    return $build;

  }

}
