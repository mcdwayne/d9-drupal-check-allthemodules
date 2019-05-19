<?php

namespace Drupal\simple_ldap;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\simple_ldap\SimpleLdapException;

interface SimpleLdapConnectionInterface {
  /**
   * Connect to the Simple LDAP server based on configuration settings.
   *
   * @throws \Drupal\simple_ldap\SimpleLdapException
   */
  public function connect();

  /**
   * Disconnect from LDAP server.
   */
  public function disconnect();

  /**
   * Get the LDAP link identifier for this SimpleLdapConnection
   *
   * @return resource
   *  LDAP link identifier
   */
  public function getResource();
}
