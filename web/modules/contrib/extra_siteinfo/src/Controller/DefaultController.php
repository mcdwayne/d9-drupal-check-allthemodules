<?php /**
 * @file
 * Contains \Drupal\extra_siteinfo\Controller\DefaultController.
 */

namespace Drupal\extra_siteinfo\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Default controller for the extra_siteinfo module.
 */
class DefaultController extends ControllerBase {

  public function extra_siteinfo_page() {
    $output = [
      'title' => [
        '#markup' => t('This Extra Site Information contains sites details like numbers of nodes, types, users, roles exist in this site.')
        ],
      'content' => [
        '#title' => 'Content',
        '#items' => extra_siteinfo_get_node_data(),
        '#theme' => 'item_list',
      ],
      'content_type' => [
        '#title' => 'Content Type',
        '#items' => extra_siteinfo_get_node_type_data(),
        '#theme' => 'item_list',
      ],
      'users' => [
        '#title' => 'Users',
        '#theme' => 'item_list',
        '#items' => extra_siteinfo_get_users_data(),
      ],
      'roles' => [
        '#title' => 'Users Role',
        '#theme' => 'item_list',
        '#items' => extra_siteinfo_get_users_role_data(),
      ],
      'currently_logged_in_users' => [
        '#title' => 'Currently Logged In Users',
        '#theme' => 'item_list',
        '#items' => extra_siteinfo_get_currently_loggedin_users_data(),
      ],
    ];
    return $output;
  }

  public function extra_siteinfo_currently_loggedin_users_page() {
    $user = \Drupal::currentUser();
    $header = [t('User Id'), t('User Name'), t('Action')];
    $rows = [];
    $query = db_select('sessions', 's');
    $query->join('users', 'u', 's.uid = u.uid');
    $query->fields('s', ['uid'])
      ->fields('u', ['name']);
    $query->orderBy('uid', 'DESC');
    $query->distinct();
    $result = $query->execute();
    $inc = 0;
    while ($record = $result->fetchAssoc()) {
      $end_session = "";
      if ($user->uid != $record['uid']) {
        // @FIXME
// l() expects a Url object, created from a route name or external URI.
// $end_session = l(t('End Session'), 'admin/reports/extra-siteinfo/currently-loggedin-users/end-session/' . $record['uid']);

      }
      // @FIXME
      // l() expects a Url object, created from a route name or external URI.
      // $rows['extra_siteinfo_users' . $inc] = array(
      //       $record['uid'],
      //       l($record['name'], 'user/' . $record['uid']),
      //       $end_session,
      //     );

      $inc = $inc + 1;
    }
    // @FIXME
    // theme() has been renamed to _theme() and should NEVER be called directly.
    // Calling _theme() directly can alter the expected output and potentially
    // introduce security issues (see https://www.drupal.org/node/2195739). You
    // should use renderable arrays instead.
    // 
    // 
    // @see https://www.drupal.org/node/2195739
    // $output = theme('table', array('header' => $header, 'rows' => $rows));

    return $output;
  }

  public function extra_siteinfo_filters() {
    $filter = [[arg(2), arg(3)]];
    if (arg(1) == 'nodes') {
      if (arg(2) == 'all') {
        $_SESSION['node_overview_filter'] = [];
      }
      else {
        $_SESSION['node_overview_filter'] = $filter;
      }
      drupal_goto('admin/content');
    }
    elseif (arg(1) == 'users') {
      if (arg(2) == 'all') {
        $_SESSION['user_overview_filter'] = [];
      }
      else {
        $_SESSION['user_overview_filter'] = $filter;
      }
      drupal_goto('admin/people');
    }
    else {
      drupal_goto('admin/reports/extra-siteinfo');
    }
  }

}
