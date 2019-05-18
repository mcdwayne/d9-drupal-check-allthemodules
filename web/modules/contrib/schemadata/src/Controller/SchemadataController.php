<?php

namespace Drupal\schemadata\Controller;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Url;

/**
 * Provides route responses for the Example module.
 */
class SchemadataController extends ControllerBase {
  /**
   * 
   * @return array
   *   Renderable array
   */
  public function show_all_tables() {
    // Query to get all tables.
    $query = db_query('SHOW TABLES')->fetchCol();
    $content = [];
    $rows = array();
    $headers = [t('Sr.No.'), t('Table Name')];
    foreach ($query as $key => $entry) {
      $link_options = array(
        'attributes' => array(
          'target' => array(
            '_blank',
          ),
        ),
      );
      $url = Url::fromRoute('schemadata.drupal-table-expend', array(
              'table_name' => $entry,
                  )
      );
      $url->setOptions($link_options);
      $internal_link = \Drupal::l(t($entry), $url);
      // Sanitize each entry.
      $rows[] = array(
        $key,
        $internal_link,
      );
    }
    $content['table'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#empty' => t('No entries available.'),
    ];
    // Don't cache this page.
    $content['#cache']['max-age'] = 0;
    return $content;
  }
  
  /**
   * 
   * @param string $table_name
   *   Table name for which we want to see rows.
   * 
   * @return string
   *   Table theme is returns
   * @throws AccessDeniedHttpException
   */
  public function explain_table($table_name = NULL) {
    $title = "Detail view of '$table_name' table";
    $request = \Drupal::request();
    if ($route = $request->attributes->get(\Symfony\Cmf\Component\Routing\RouteObjectInterface::ROUTE_OBJECT)) {
      $route->setDefault('_title', $title);
    }
    $all_tables = db_query('SHOW TABLES')->fetchCol();
    $flag = FALSE;
    if (in_array($table_name, $all_tables)) {
      $flag = TRUE;
    }
    if ($flag) {
      $cols = db_query("SHOW columns FROM $table_name")->fetchCol();
      $sorted_field = array();
      foreach ($cols as $field) {
        // Make array for sorting the coloum name.
        $sorted_field[] = array('data' => $this->t('@field', array('@field' => $field)), 'field' => $field);
      }
      $db = \Drupal::database();
      $query = $db->select($table_name, 'n');
      $table_sort = $query->extend('Drupal\Core\Database\Query\TableSortExtender')->orderByHeader($sorted_field);
      $pager = $table_sort->extend('Drupal\Core\Database\Query\PagerSelectExtender');
      $query->fields('n');
      $result = $pager->execute();
      $refine_rows = array();
      foreach ($result as $table_row) {
        $refined_data_rows = array();
        foreach ($table_row as $data) {
          $refined_data_rows[] = Xss::filter($data);
        }
        $refine_rows[] = $refined_data_rows;
      }
      // Generate the table.
      $build['config_table'] = array(
        '#theme' => 'table',
        '#header' => $sorted_field,
        '#rows' => $refine_rows,
        '#empty' => t('No entries available.'),
      );
      // Finally add the pager.
      $build['pager'] = array(
        '#type' => 'pager'
      );

      return $build;
    } else {
      throw new AccessDeniedHttpException();
    }
  }
}
