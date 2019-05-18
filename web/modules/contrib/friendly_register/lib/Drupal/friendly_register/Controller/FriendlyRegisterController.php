<?php

/**
 * @file
 * Contains \Drupal\friendly_register\Controller\FriendlyRegisterController.
 */

namespace Drupal\friendly_register\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

class FriendlyRegisterController extends ControllerBase {
  public function checkEmail($address) {
    $result = db_query('SELECT uid FROM {users} WHERE mail = :user_email',
      array('user_email' => $address))
      ->fetchObject();

    return new JsonResponse(array('available' => ($result == null)));
  }

  public function checkUser($username) {
    $result = db_query('SELECT uid FROM {users} WHERE name = :username',
      array('username' => $username))
      ->fetchObject();

    return new JsonResponse(array('available' => ($result == null)));
  }
}
