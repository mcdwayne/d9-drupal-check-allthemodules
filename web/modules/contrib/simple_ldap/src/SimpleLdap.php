<?php

namespace Drupal\simple_ldap;

use Drupal\simple_ldap\SimpleLdapException;
use Drupal\simple_ldap\SimpleLdapConnection;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\simple_ldap\SimpleLdapConnectionInterface;

/**
 * A wrapper for PHP's LDAP functions, with associated helper methods.
 */
class SimpleLdap {
  private $connection;

  /**
   * @var boolean
   */
  private $bound;

  public function __construct(SimpleLdapConnectionInterface $connection) {
    $this->connection = $connection;
    $this->bound = FALSE;
  }

  public function __destruct() {
    $this->ldapUnbind();
  }

  /**
   * Cleans up an array returned by the ldap_* functions.
   *
   * @param array $entry
   *   An LDAP entry as returned by SimpleLdapServer::search()
   *
   * @return array
   *   A scrubbed array, with all of the "extra crud" removed, with the DN of the record as the array index.
   *
   * @throws SimpleLdapException
   */
  public function clean($entry) {
    if (!is_array($entry)) {
      throw new SimpleLdapException('Can only clean an array.');
    }

    $clean = array();

    // Yes, this is ugly, but so are the ldap_*() results.
    for ($i = 0; $i < $entry['count']; $i++) {
      $clean[$entry[$i]['dn']] = array();
      for ($j = 0; $j < $entry[$i]['count']; $j++) {
        $clean[$entry[$i]['dn']][$entry[$i][$j]] = array();
        for ($k = 0; $k < $entry[$i][$entry[$i][$j]]['count']; $k++) {
          $clean[$entry[$i]['dn']][$entry[$i][$j]][] = $entry[$i][$entry[$i][$j]][$k];
        }
      }
    }

    return $clean;
  }

  /**
   * Cleans an attribute array, removing empty items.
   *
   * @param array $attributes
   *   Array of attributes that needs to be cleaned.
   * @param boolean $strip_empty_array
   *   Determines whether an attribute consisting of an empty array should be
   *   stripped or left intact. Defaults to TRUE.
   *
   * @return array
   *   A scrubbed array with no empty attributes.
   */
  public function removeEmptyAttributes($attributes, $strip_empty_array = TRUE) {
    foreach ($attributes as $key => $value) {
      if (is_array($value)) {
        // Remove empty values.
        foreach ($value as $k => $v) {
          if (empty($v)) {
            unset($attributes[$key][$k]);
          }
        }

        // Remove the 'count' property.
        unset($value['count']);
        unset($attributes[$key]['count']);

        // Remove attributes with no values.
        if ($strip_empty_array && count($attributes[$key]) == 0) {
          unset($attributes[$key]);
        }
      }
    }

    return $attributes;
  }

  /**
   * Generates a random salt of the given length.
   */
  public function salt($length) {
    $possible = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ./';
    $str = '';

    while (strlen($str) < $length) {
      $str .= substr($possible, (rand() % strlen($possible)), 1);
    }

    return $str;
  }

  /**
   * Hash a string for use in an LDAP password field.
   */
  public function hash($string, $algorithm = NULL) {
    switch ($algorithm) {
      case 'crypt':
        $hash = '{CRYPT}' . crypt($string, substr($string, 0, 2));
        break;

      case 'salted crypt':
        $hash = '{CRYPT}' . crypt($string, self::salt(2));
        break;

      case 'extended des':
        $hash = '{CRYPT}' . crypt($string, '_' . self::salt(8));
        break;

      case 'md5crypt':
        $hash = '{CRYPT}' . crypt($string, '$1$' . self::salt(9));
        break;

      case 'blowfish':
        $hash = '{CRYPT}' . crypt($string, '$2a$12$' . self::salt(13));
        break;

      case 'md5':
        $hash = '{MD5}' . base64_encode(md5($string, TRUE));
        break;

      case 'salted md5':
        $salt = SimpleLdap::salt(8);
        $hash = '{SMD5}' . base64_encode(md5($string . $salt, TRUE) . $salt);
        break;

      case 'sha':
        $hash = '{SHA}' . base64_encode(sha1($string, TRUE));
        break;

      case 'salted sha':
        $salt = SimpleLdap::salt(8);
        $hash = '{SSHA}' . base64_encode(sha1($string . $salt, TRUE) . $salt);
        break;

      case 'unicode':
        $string = '"' . $string . '"';
        $length = drupal_strlen($string);
        $hash = NULL;
        for ($i = 0; $i < $length; $i++) {
          $hash .= "{$string{$i}}\000";
        }
        break;

      case 'none':
      default:
        $hash = $string;
    }

    return $hash;
  }

  /**
   * Returns an array of supported hash types.
   *
   * The keys of this array are also the values supported by SimpleLdap::hash().
   * The values are translated, human-readable values.
   */
  public static function hashes() {
    $types = array();

    // Crypt, and Salted Crypt.
    $types['crypt'] = t('Crypt');
    $types['salted crypt'] = t('Salted Crypt');

    // Extended DES.
    if (defined('CRYPT_EXT_DES') || CRYPT_EXT_DES == 1) {
      $types['extended des'] = t('Extended DES');
    }

    // MD5Crypt.
    if (defined('CRYPT_MD5') || CRYPT_MD5 == 1) {
      $types['md5crypt'] = t('MD5Crypt');
    }

    // Blowfish.
    if (defined('CRYPT_BLOWFISH') || CRYPT_BLOWFISH == 1) {
      $types['blowfish'] = t('Blowfish');
    }

    // MD5
    $types['md5'] = t('MD5');

    // SMD5.
    $types['salted md5'] = t('Salted MD5');

    // SHA.
    $types['sha'] = t('SHA');

    // SSHA.
    $types['salted sha'] = t('Salted SHA');

    // Unicode (used by Active Directory).
    $types['unicode'] = t('Unicode');

    return $types;
  }

  /**
   * Wrapper function for ldap_bind().
   *
   * @param string $bind_rdn
   *   The RDN to bind with. If not specified, and anonymous bind is attempted.
   * @param string $bind_password
   *   The password to use during the bind.
   *
   * @return boolean
   *   Returns TRUE on success or FALSE on failure.
   */
  public function ldapBind($bind_rdn = NULL, $bind_password = NULL) {
    $bound = @ldap_bind($this->connection->getResource(), $bind_rdn, $bind_password);

    $this->bound = $bound;
    return $bound;
  }

  /**
   * Wrapper function for ldap_unbind().
   *
   * @return boolean
   *   TRUE on success
   *
   * @throws SimpleLdapException
   */
  public function ldapUnbind() {

    $return = FALSE;

    // Check that the LDAP connection is currently bound.
    if ($this->bound && $this->connection->getResource()) {
      // If unbinding is successful, $return should be TRUE.
      $return = @ldap_unbind($this->connection->getResource());
      if (!$return) {
        throw new SimpleLdapException('ldap_unbind wrapper: ', $this->connection->getResource());
      }
    }

    $this->bound = FALSE;

    return $return;
  }

  /**
   * Wrapper function for ldap_control_paged_result().
   *
   * @param int $pagesize
   *  The number of entries by page.
   * @param bool $iscritical
   *  Indicates whether the pagination is critical of not. If true and if the
   *  server doesn't support pagination, the search will return no result.
   * @param string $cookie
   *  An opaque structure sent by the server.
   * @return bool
   *  TRUE on success
   *
   * @throws \Drupal\simple_ldap\SimpleLdapException
   */
  public function controlPagedResult($pagesize, $iscritical, $cookie) {
    $return = @ldap_control_paged_result($this->connection->getResource(), $pagesize, $iscritical, $cookie);

    if ($return === FALSE) {
      throw new SimpleLdapException('ldap_control_paged_result wrapper: ', $this->connection->getResource());
    }

    return $return;
  }

  /**
   * Wrapper function for ldap_control_paged_result_response().
   *
   * @param resource $result
   *   An LDAP search result identifier.
   * @param string $cookie
   *   An opaque structure sent by the server.
   * @param int $estimated
   *   The estimated number of entries to retrieve.
   *
   * @return boolean
   *   TRUE on success.
   */
  public function controlPageResultResponse($result, &$cookie = NULL, &$estimated = NULL) {
    $return = @ldap_control_paged_result_response($this->connection->getResource(), $result, $cookie, $estimated);

    return $return;
  }

  /**
   * @param string $base_dn
   *   The base DN for the directory.
   * @param string $filter
   *   The LDAP filter to apply.
   * @param array $attributes
   *   An array of the required attributes.
   * @param int $attrsonly
   *   Should be set to 1 if only attribute types are wanted. If set to 0 both
   *   attributes types and attribute values are fetched which is the default
   *   behaviour.
   * @param int $sizelimit
   *   Enables you to limit the count of entries fetched. Setting this to 0
   *   means no limit.
   * @param int $timelimit
   *   Sets the number of seconds how long is spend on the search. Setting this
   *   to 0 means no limit.
   * @param int $deref
   *   Specifies how aliases should be handled during the search.
   * @return resource'
   *   LDAP search result identifier.
   *
   * @throws \Drupal\simple_ldap\SimpleLdapException
   */
  public function ldapRead($base_dn, $filter, $attributes = array(), $attrsonly = 0, $sizelimit = 0, $timelimit = 0, $deref = NULL) {

    $return = @ldap_read($this->connection->getResource(), $base_dn, $filter, $attributes, $attrsonly, $sizelimit, $timelimit, $this->getDefaultDeref($deref));

    if ($return === FALSE) {
      throw new SimpleLdapException('ldap_read wrapper: ', $this->connection->getResource());
    }

    return $return;
  }

  /**
   * Wrapper function for ldap_list().
   *
   * @param string $base_dn
   *   The base DN for the directory.
   * @param string $filter
   *   The LDAP filter to apply.
   * @param array $attributes
   *   An array of the required attributes.
   * @param int $attrsonly
   *   Should be set to 1 if only attribute types are wanted. If set to 0 both
   *   attributes types and attribute values are fetched which is the default
   *   behaviour.
   * @param int $sizelimit
   *   Enables you to limit the count of entries fetched. Setting this to 0
   *   means no limit.
   * @param int $timelimit
   *   Sets the number of seconds how long is spend on the search. Setting this
   *   to 0 means no limit.
   * @param int $deref
   *   Specifies how aliases should be handled during the search.
   *
   * @return resource
   *   LDAP search result identifier.
   *
   * @throws SimpleLdapException
   */
  public function ldapList($base_dn, $filter, $attributes = array(), $attrsonly = 0, $sizelimit = 0, $timelimit = 0, $deref = NULL) {
    $return = @ldap_list($this->connection->getResource(), $base_dn, $filter, $attributes, $attrsonly, $sizelimit, $timelimit, $this->getDefaultDeref($deref));

    if ($return === FALSE) {
      throw new SimpleLdapException('ldap_list wrapper: ', $this->connection->getResource());
    }

    return $return;
  }

  /**
   * Wrapper function for ldap_search().
   *
   * @param string $base_dn
   *   The base DN for the directory.
   * @param string $filter
   *   The LDAP filter to apply.
   * @param array $attributes
   *   An array of the required attributes.
   * @param int $attrsonly
   *   Should be set to 1 if only attribute types are wanted. If set to 0 both
   *   attributes types and attribute values are fetched which is the default
   *   behaviour.
   * @param int $sizelimit
   *   Enables you to limit the count of entries fetched. Setting this to 0
   *   means no limit.
   * @param int $timelimit
   *   Sets the number of seconds how long is spend on the search. Setting this
   *   to 0 means no limit.
   * @param int $deref
   *   Specifies how aliases should be handled during the search.
   *
   * @return resource
   *   LDAP search result identifier.
   *
   * @throws SimpleLdapException
   */
  public function ldapSearch($base_dn, $filter, $attributes = array(), $attrsonly = 0, $sizelimit = 0, $timelimit = 0, $deref = NULL) {
    $return = @ldap_search($this->connection->getResource(), $base_dn, $filter, $attributes, $attrsonly, $sizelimit, $timelimit, $this->getDefaultDeref($deref));

    if ($return === FALSE) {
      throw new SimpleLdapException('ldap_search wrapper: ', $this->connection->getResource());
    }

    return $return;
  }

  /**
   * Wrapper function for ldap_get_entries().
   *
   * @param resource $result_identifier
   *   An LDAP search result identifier.
   *
   * @return array
   *   An array of LDAP entries.
   *
   * @throws SimpleLdapException
   */
  public function getEntries($result_identifier) {
    $return = @ldap_get_entries($this->connection->getResource(), $result_identifier);

    // Error handling.
    if ($return === FALSE) {
      throw new SimpleLdapException('ldap_get_entries wrapper: ', $this->connection->getResource());
    }

    return $return;
  }

  /**
   * Wrapper function for ldap_free_result().
   *
   * @param resource $result_identifier
   *   LDAP search result identifier.
   *
   * @return boolean
   *   TRUE on success.
   *
   * @throws SimpleLdapException
   */
  public  function freeResult($result_identifier) {
    $return = @ldap_free_result($result_identifier);

    // Error handling.
    if ($return === FALSE) {
      throw new SimpleLdapException('ldap_free_result wrapper: ', $result_identifier);
    }

    return $return;
  }

  /**
   * Whether the wrapper has a successful binding or not.
   *
   * @return bool
   */
  public function isBound() {
    return $this->bound;
  }

  /**
   * Wrapper function for ldap_connect().
   *
   * @throws \Drupal\simple_ldap\SimpleLdapException
   */
  public function connect() {
    $this->connection->connect();
  }

  /**
   * Wrapper function for ldap_close().
   */
  public function disconnect() {
    $this->connection->disconnect();
    $this->bound = FALSE;
  }

  /**
   * Helper function to keep constant out of the function parameter signature.
   *
   * @param $deref
   * @return int
   */
  protected function getDefaultDeref($deref) {
    if ($deref == NULL) {
      return LDAP_DEREF_NEVER;
    }

    return $deref;
  }
}

