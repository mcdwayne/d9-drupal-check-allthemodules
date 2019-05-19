<?php

namespace Drupal\simple_ldap\Controller;

use Drupal\Core\Controller\ControllerBase;

class SimpleLdapTestController extends ControllerBase {

  public function test() {
//    $server = \Drupal::service('simple_ldap.server');
//
//    $server->connect();
//    $server->bind();
//    $results = $server->search('dc=local', 'cn=*', 'sub');

    $auth = \Drupal::service('simple_ldap_user.auth');
    $dn = $auth->getUserDN('ldapuser');

    return array(
      '#type' => 'markup',
      '#markup' => $dn,
    );
  }
}