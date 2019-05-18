<?php

namespace Drupal\moodle_integration\Services;

use Drupal\moodle_integration\Utility;
use \Drupal\Core\Database\Connection;

/**
 * Class UserService.
 */

class UserService {

  function moodleCreateUser($users) {
    // $baseurl = 'http://localhost/moodle/webservice/rest/server.php?';
    // //print_r($users);die;
    // $params = array(
    // 'wstoken' => 'a5f4f1801d6268ad29b11ffcb51942d9',
    // 'wsfunction' => 'core_user_create_users',
    // 'moodlewsrestformat' => 'json',
    // 'users' => $users,
    // );

    // $url = $baseurl . http_build_query($params);
    // $response = file_get_contents($url);
    // $newusers = json_decode($response);

    // return $newusers[0]->id;

  }

  function moodleUpdateUser($users) {
    $params = array(
      'wstoken' => 'a5f4f1801d6268ad29b11ffcb51942d9',
      'wsfunction' => 'core_user_update_users',
      'moodlewsrestformat' => 'json',
      'users' => $users,
    );
    $url = $baseurl . http_build_query($params);
    $response = file_get_contents($url);
    $newusers = json_decode($response);
  }
}


