<?php

namespace Drupal\loggedin_users_list\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Render\FormattableMarkup;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class LoggedinUsersListController.
 */
class LoggedinUsersListController extends ControllerBase {

  /**
   * Here this function showing a list of logged-in users.
   */
  public function loggedinUsers() {
    // Count currently, how many number of users are logged-in on the website.
    $query_count = \Drupal::database()->select('sessions', 's');
    $query_count->addExpression('COUNT(DISTINCT(uid))');
    $count_rc = $query_count->execute()->fetchField();

    /* Display List of currently logged-in users.
    Use this link (https://www.drupal.org/project/drupal/issues/2939760)
    to remove ",ONLY_FULL_GROUP_BY" word from location
    "\core\lib\Drupal\Core\Database\Driver\mysql\Connection.php" at line no. 455
    OR it can be setting into settings.php and add below code

    $databases['default']['default'] = array (
    'database' => 'database',
    'username' => 'root',
    'password' => '****',
    'prefix' => '',
    'host' => 'localhost',
    'port' => '3306',
    'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
    'driver' => 'mysql',
    'init_commands' => [
    'sql_mode' => "SET sql_mode = 'ANSI,STRICT_TRANS_TABLES,STRICT_ALL_TABLES,
    NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER'",
    ],
    );
     */

    $query_list = \Drupal::database()->select('sessions', 's');
    $query_list->leftJoin('users_field_data', 'u', 'u.uid = s.uid');
    $query_list->addExpression('COUNT(s.uid)', 'count_uid');
    $query_list->fields('s', ['uid', 'sid', 'hostname', 'timestamp']);
    $query_list->fields('u', ['name']);
    $query_list->groupBy('s.uid');
    $query_list->orderBy('s.uid', 'ASC');
    $result_rc = $query_list->execute()->fetchAll();

    $header = [
      'userid' => t('User id'),
      'username' => t('Username'),
      'nod' => t('Number of Logged-in (Devices or Browser)'),
      'hostname' => t('Hostname'),
      'loggedin' => t('Loggedin Time'),
      'operation' => t('Operation'),
    ];

    $header_inner = [
      'hostname'  => '',
      'timestamp' => '',
      'operation' => '',
    ];

    $rows_data = [];
    foreach ($result_rc as $result) {
      if ($result->count_uid > 1) {
        $query_list_inner = '';
        $query_list_inner = \Drupal::database()->select('sessions', 's');
        $query_list_inner->fields('s', ['sid', 'hostname', 'timestamp']);
        $query_list_inner->fields('s', ['sid', 'hostname', 'timestamp']);
        $query_list_inner->condition('uid', $result->uid, '=');
        $query_list_inner->orderBy('s.timestamp', 'ASC');
        $result_rc_inner = $query_list_inner->execute()->fetchAll();

        $rows_data_inner = [];
        foreach ($result_rc_inner as $result_inner) {
          $rows_data_inner[] = [
            'hostname' => $result_inner->hostname,
            'loggedin' => date("Y-m-d H:i:s", $result_inner->timestamp),
            'operation' => [
              'data' => new FormattableMarkup('<a href=":link">Log out</a>',
                [
                  ':link' => '/admin/config/people/loggedin-users-list/' . $result_inner->sid,
                ]
              ),
            ],
          ];
        }

        $build_inner = ['#markup' => ''];
        $build_inner['table'] = [
          '#theme' => 'table',
          '#header' => '',
          '#rows' => $rows_data_inner,
        ];

        $rows_data[] = [
          'userid'   => $result->uid,
          'username' => [
            'data' => new FormattableMarkup('<a href=":link">' . $result->name . '</a>',
              [
                ':link' => '/user/' . $result->uid,
              ]
            ),
          ],
          'nod'      => $result->count_uid,
          'hostname' => ['data' => $build_inner, 'colspan' => 3],
        ];
      }
      else {
        $rows_data[] = [
          'userid'   => $result->uid,
          'username' => [
            'data' => new FormattableMarkup('<a href=":link">' . $result->name . '</a>',
              [
                ':link' => '/user/' . $result->uid,
              ]
            ),
          ],
          'nod'      => $result->count_uid,
          'hostname' => $result->hostname,
          'loggedin' => date("Y-m-d H:i:s", $result->timestamp),
          'operation' => [
            'data' => new FormattableMarkup('<a href=":link">Log out</a>',
              [
                ':link' => '/admin/config/people/loggedin-users-list/' . $result->sid,
              ]
            ),
          ],
        ];
      }
    }

    $build = ['#markup' => ''];

    $build['table'] = [
      '#prefix' => '<table><tr><td><b>Currently number of logged-in users: </b></td><td><b>' . $count_rc . '</b></td></tr></table>',
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows_data,
    ];
    return $build;
  }

  /**
   * Here admin can loged out any user.
   */
  public function logoutUser($logout) {
    $query_delete = \Drupal::database()->delete('sessions');
    $query_delete->condition('sid', $logout);
    $query_delete->execute();
    drupal_set_message(t('User log out successfully.'));
    $response = new RedirectResponse('/admin/config/people/loggedin-users-list');
    return $response->send();
  }

}
