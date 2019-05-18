<?php

namespace Drupal\consistent_uid_test\Controller;

use Drupal\Component\Utility\Random;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ConsistentUidTestController.
 *
 * @package Drupal\consistent_uid_test\Controller
 */
class ConsistentUidTestController extends ControllerBase {

  public function access($auth_token) {

    if ($auth_token == sha1('keep_calm_dude')) {

      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }

  public function userCreateHandler($user_name) {

    // Create a user assigned to that role.
    $edit = [];
    $edit['name'] = !empty($user_name) ? $user_name : (new Random())->name(8, TRUE);
    $edit['mail'] = $edit['name'] . '@example.com';
    $edit['pass'] = user_password();
    $edit['status'] = 1;
    $account = User::create($edit);
    $account->save();
    $this->getLogger('consistent_uid_test')->info('User @user created.', [
      '@user' => $edit['name'],
    ]);

    return new Response($this->t("Hi dude, user @user created.", [
      '@user' => $edit['name'],
    ]), 200);
  }

}