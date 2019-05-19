<?php
/**
 * Created by PhpStorm.
 * User: e0ipso
 * Date: 2019-01-08
 * Time: 13:02
 */

namespace Drupal\simple_ldap_user\Events;


use Drupal\simple_ldap_user\SimpleLdapUser;
use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class SimpleLdapUserEvent extends GenericEvent {

  /**
   * @var \Drupal\simple_ldap_user\SimpleLdapUser
   */
  protected $ldapUser;

  /**
   * @var \Drupal\user\UserInterface
   */
  protected $drupalUser;

  /**
   * SimpleLdapUserEvent constructor.
   *
   * @param \Drupal\simple_ldap_user\SimpleLdapUser $ldap_user
   * @param \Drupal\user\UserInterface $drupal_user
   */
  public function __construct(SimpleLdapUser $ldap_user, UserInterface $drupal_user) {
    $this->ldapUser = $ldap_user;
    $this->drupalUser = $drupal_user;
  }


  /**
   * @return \Drupal\simple_ldap_user\SimpleLdapUser
   */
  public function getLdapUser() {
    return $this->ldapUser;
  }

  /**
   * @return \Drupal\user\UserInterface
   */
  public function getDrupalUser() {
    return $this->drupalUser;
  }

}
