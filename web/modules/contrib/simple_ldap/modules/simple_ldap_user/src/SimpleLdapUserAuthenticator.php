<?php

namespace Drupal\simple_ldap_user;

use Drupal\simple_ldap\SimpleLdapServer;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\simple_ldap\SimpleLdapException;
use Drupal\user\UserInterface;

class SimpleLdapUserAuthenticator {

  /**
   * @var SimpleLdapServer
   */
  protected $server;

  /**
   * @var ImmutableConfig
   */
  protected $config;

  public function __construct(SimpleLdapServer $server, ConfigFactoryInterface $config_factory) {
    $this->server = $server;
    $this->server->connect();
    $this->config = $config_factory->get('simple_ldap.user');
  }

  /**
   * Attempt to authenticate against the LDAP server.
   *
   * @param $dn
   *  DN (Distinguished Name) of the user.
   * @param $password
   *
   * @return boolean
   *  TRUE on success, FALSE on failure.
   */
  public function authenticate($dn, $password) {
    return $this->server->bind($dn, $password, TRUE);
  }

  /**
   * Whether the given name should be checked against the LDAP server.
   *
   * @param string $name
   *  A username that might or not be an existing user.
   *
   * @return boolean
   *  TRUE if the user should be authenticated against the LDAP server. FALSE if it is the username for Drupal user 1.
   */
  public function canAuthenticate($name) {
    $db = \Drupal::database();
    $result = $db->select('users_field_data', 'ud')
      ->fields('ud', array('name'))
      ->condition('uid', 1)
      ->execute()
      ->fetchField();

    if ($result == $name) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Whether we should skip any LDAP checks on a User object.
   *
   * @param $user
   *  A User object.
   *
   * @return boolean
   *  TRUE if this User should be skipped and LDAP ignored.
   */
  public function skipCheck(UserInterface $user) {
    $uid = $user->get('uid')->value;

    // If User 1 or anonymous or blocked...
    if ($uid == 1 || $user->isAnonymous() || $user->isBlocked()) {
      return TRUE;
    }

    return FALSE;
  }
}
