<?php

namespace Drupal\simple_ldap_user;

class SimpleLdapUser {

  /**
   * @var array
   */
  protected $attributes;

  /**
   * @var string
   */
  protected $dn;

  public function __construct($dn, $attributes) {
    $this->dn = $dn;
    $this->attributes = $attributes;
  }

  public function getDn() {
    return $this->dn;
  }

  public function getAttributes() {
    return $this->attributes;
  }
}
