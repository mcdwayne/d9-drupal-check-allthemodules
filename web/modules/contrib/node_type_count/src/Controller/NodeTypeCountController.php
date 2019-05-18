<?php

/**
 * @file
 * Contains \Drupal\node_type_count\Controller\NodeTypeCountController.
 */

namespace Drupal\node_type_count\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use \Drupal\Component\Utility\SafeMarkup;

/**
 * Controller routines for page example routes.
 */
class NodeTypeCountController extends ControllerBase {

  /**
   * Constructs a page with descriptive content.
   *
   * Our router maps this method to the path 'admin/reports/node-type-count'.
   */
  public function nodeTypeCountPublished() {
    // We are going to output the results in a table with a nice header.
    $header  = array(
      t('Title'),
      t('Type'),
      t('Published'),
      t('UnPublished'),
    );

    $result = node_type_get_names();
    if (is_array($result)) {
      foreach ($result as $node_type_machine_name => $content_type_title) {
        // Get the value as key and value pair.
        $result_arr['title'] = SafeMarkup::checkPlain($content_type_title);
        $result_arr['machine_name'] = $node_type_machine_name;
        $result_arr['published'] = NodeTypeCountController::nodeCountState(NODE_PUBLISHED, $node_type_machine_name);
        $result_arr['unpublished'] = NodeTypeCountController::nodeCountState(NODE_NOT_PUBLISHED, $node_type_machine_name);
        $result_final[$node_type_machine_name] = $result_arr;
      }
    }
    $rows = array();
    foreach ($result_final as $row) {
      // Normally we would add some nice formatting to our rows
      // but for our purpose we are simply going to add our row
      // to the array.
      $rows[] = array('data' => (array) $row);
    }
    // Build the table for the nice output.
    $build = array(
      '#markup' => '<p>' . t('The layout here is a themed as a table
           that is sortable by clicking the header name.') . '</p>',
    );
    $build['tablesort_table'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    );

    return $build;

  }

  /**
   * This code (noted in the callback above) creates the.
   *
   * Contents of the page for User count.
   */
  public function userRoleCount() {
    // We are going to output the results in a table with a nice header.
    $header  = array(
      t('Role Name'),
      t('Role Machine Name'),
      t('Number of Users'),
    );
    $results = user_role_names();
    if (is_array($results)) {
      foreach ($results as $user_role_machine_name => $content_type_title) {
        // Get the value as key and value pair.
        $result_arr['title'] = SafeMarkup::checkPlain($content_type_title);
        $result_arr['machine_name'] = $user_role_machine_name;
        $result_arr['count'] = NodeTypeCountController::userCountByRole($user_role_machine_name);
        $result_final[$user_role_machine_name] = $result_arr;
      }
    }
    $rows = array();
    foreach ($result_final as $row) {
      // Normally we would add some nice formatting to our rows
      // but for our purpose we are simply going to add our row
      // to the array.
      $rows[] = array('data' => (array) $row);
    }
    // Build the table for the nice output.
    $build = array(
      '#markup' => '<p>' . t('The layout here is a themed as a table
           that is sortable by clicking the header name.') . '</p>',
    );
    $build['tablesort_table'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    );

    return $build;

  }

  /**
   * This is the helper function for nodeCountState() to get the count.
   *
   * Of the published or unpublished content of particular content type.
   *
   * @param bool $status
   *   Node status.
   * @param string $type
   *   Machine name of the content type.
   *
   * @return numeric
   *   Returns the count of node published or unpublished in the Content Type.
   */
  public function nodeCountState($status, $type) {
    $query = \Drupal::entityQuery('node')
            ->condition('status', $status)
            ->condition('type', $type);
    $result = $query->count()->execute();
    return $result;
  }

  /**
   * Count User Role.
   */
  public function userCountByRole($role_type_machine_name) {
    $query = db_select('user__roles', 'ur')
            ->fields('ur', array('entity_id'))
            ->condition('roles_target_id', $role_type_machine_name);
    $results = $query->countQuery()->execute()->fetchField();
    return $results;
  }

}
