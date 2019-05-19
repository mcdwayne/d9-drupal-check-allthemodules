<?php

namespace Drupal\simple_ldap;

class SimpleLdapException extends \Exception {
  /**
   * Constructs a SimpleLdapException.
   */
  public function __construct($message, $resource) {
    if (is_resource($resource)) {
      // Handle LDAP operation errors.
      $error = ldap_errno($resource);
      $message = $message . ldap_err2str($error);
      parent::__construct($message, $error);
    }
    else {
      // Handle exceptions that are not related to an LDAP resource link.
      parent::__construct($message);
    }
  }
}
