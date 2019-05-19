<?php

namespace Drupal\simple_ldap;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\simple_ldap\SimpleLdap;
use Drupal\simple_ldap\SimpleLdapServerSchema;

class SimpleLdapServer {

  /**
   * The Simple LDAP server configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The LDAP function wrapper.
   *
   * @var \Drupal\simple_ldap\SimpleLdap
   */
  protected $ldap;

  /**
   * @var int
   */
  protected $pagesize = FALSE;

  /**
   * @var array
   */
  protected $rootdse;

  /**
   * @var string
   */
  protected $type;

  /**
   * Constructs a SimpleLdapServer.
   *
   * @param ConfigFactoryInterface $config_factory
   * @param \Drupal\simple_ldap\SimpleLdap $ldap
   */
  public function __construct(ConfigFactoryInterface $config_factory, SimpleLdap $ldap) {
    $this->config = $config_factory->get('simple_ldap.server');
    $this->ldap = $ldap;
    $this->connect();
  }

  /**
   * Connect and bind to the LDAP server.
   *
   * @param $binddn
   *  Use another Bind DN instead of the one provided in the settings form.
   * @param $bindpw
   *  Use another Bind PW instead of the one provided in the settings form.
   * @param boolean $rebind
   *  Rebind the connection, even if it is already bound.
   *
   * @return boolean
   *  TRUE on successful connection.
   */
  public function bind($binddn = NULL, $bindpw = NULL, $rebind = FALSE) {
    if ($this->ldap->isBound() && !$rebind) {
      return TRUE;
    }

    // Use provided credentials if present, otherwise use config settings.
    $binddn = ($binddn == NULL) ? $this->config->get('binddn') : $binddn;
    $bindpw = ($bindpw == NULL) ? $this->config->get('bindpw') : $bindpw;

    $this->ldap->ldapBind($binddn, $bindpw);

    return $this->ldap->isBound();
  }

  /**
   * Search the LDAP server.
   *
   * @param string $base_dn
   *   LDAP search base.
   * @param string $filter
   *   LDAP search filter.
   * @param string $scope
   *   LDAP search scope. Valid values are 'sub', 'one', and 'base'.
   * @param array $attributes
   *   Array of attributes to retrieve.
   * @param int $attrsonly
   *   Set to 1 in order to retrieve only the attribute names without the
   *   values. Set to 0 (default) to retrieve both the attribute names and
   *   values.
   * @param int $sizelimit
   *   Client-side size limit. Set this to 0 to indicate no limit. The server
   *   may impose stricter limits.
   * @param int $timelimit
   *   Client-side time limit. Set this to 0 to indicate no limit. The server
   *   may impose stricter limits.
   * @param int $deref
   *   Specifies how aliases should be handled during the search.
   *
   * @return array
   *   Search results.
   *
   * @throws SimpleLdapException
   */
  public function search($base_dn, $filter = 'objectclass=*', $scope = 'sub', $attributes = array(), $attrsonly = 0, $sizelimit = 0, $timelimit = 0, $deref = NULL) {
    // Make sure there is a valid binding.
    if (!$this->ldap->isBound()) {
      $this->bind();
    }

    try {
      // Use a post-test loop (do/while) because this will always be done once.
      // It will only loop if paged queries are supported/enabled, and more than
      // one page is available.
      $entries = array('count' => 0);
      $cookie = '';
      do {

        if ($this->pagesize) {
          // Set the paged query cookie.
          $this->ldap->controlPagedResult($this->pagesize, FALSE, $cookie);
        }

        // Perform the search based on the scope provided.
        switch ($scope) {
          case 'base':
            $result = $this->ldap->ldapRead($base_dn, $filter, $attributes, $attrsonly, $sizelimit, $timelimit, $deref);
            break;

          case 'one':
            $result = $this->ldap->ldapList($base_dn, $filter, $attributes, $attrsonly, $sizelimit, $timelimit, $deref);
            break;

          case 'sub':
          default:
            $result = $this->ldap->ldapSearch($base_dn, $filter, $attributes, $attrsonly, $sizelimit, $timelimit, $deref);
            break;
        }

        if ($this->pagesize) {
          // Merge page into $entries.
          $e = $this->ldap->getEntries($result);
          $entries['count'] += $e['count'];
          for ($i = 0; $i < $e['count']; $i++) {
            $entries[] = $e[$i];
          }

          // Get the paged query response cookie.
         $this->ldap->controlPageResultResponse($result, $cookie);
        }
        else {
          $entries = $this->ldap->getEntries($result);
        }

        // Free the query result memory.
        $this->ldap->freeResult($result);

      } while ($cookie !== NULL && $cookie != '');

    } catch (SimpleLdapException $e) {
      // Error code 32 means there were no matching search results.
      if ($e->getCode() == 32) {
        $entries = array('count' => 0);
      }
      else {
        throw $e;
      }
    }

    // ldap_get_entries returns NULL if ldap_read does not find anything.
    // Reformat the result into something consistent with the other search
    // types.
    if ($entries === NULL) {
      $entries = array('count' => 0);
    }

    return $this->ldap->clean($entries);
  }

  /**
   * Set the pagesize from the config settings.
   */
  protected function setPageSize() {
    $this->pagesize = $this->config->get('pagesize');
  }

  /**
   * If the LDAP server supports paged queries, set the pagesize for future queries.
   *
   * @return mixed
   *  Integer if a pagesize exists and it is supported, FALSE if otherwise.
   */
  public function getPageSize() {
    $this->setPageSize();
    if ($this->ldap->isBound() && $this->pagesize) {
      $this->setRootDse();

      // Look for the paged query OID supported control.
      if (!in_array('1.2.840.113556.1.4.319', $this->rootdse['supportedcontrol'])) {
        $this->pagesize = FALSE;
      }
    }

    return $this->pagesize;
  }

  /**
   * Attempts to detect the directory type using the rootDSE.
   *
   * @return string
   *  The server type: OpenLDAP, Active Directory, or LDAP.
   */
  public function getServerType() {
    if (isset($this->type)) {
      return $this->type;
    }

    if (empty($this->rootdse) && $this->ldap->isBound()) {
      $this->setRootDse();
    }

    // Check for OpenLDAP.
    if (isset($this->rootdse['objectclass']) && is_array($this->rootdse['objectclass'])) {
      if (in_array('OpenLDAProotDSE', $this->rootdse['objectclass'])) {
        $this->type = 'OpenLDAP';
        return $this->type;
      }
    }

    // Check for Active Directory.
    if (isset($this->rootdse['rootdomainnamingcontext'])) {
      $this->type = 'Active Directory';
      return $this->type;
    }

    // Default to generic LDAPv3.
    $this->type = 'LDAP';
    return $this->type;
  }

  /**
   * Loads the server's rootDSE.
   *
   * @throw SimpleLdapException
   */
  protected function setRootDse() {
    if (!is_array($this->rootdse)) {
      $attributes = array(
        'vendorName',
        'vendorVersion',
        'namingContexts',
        'altServer',
        'supportedExtension',
        'supportedControl',
        'supportedSASLMechanisms',
        'supportedLDAPVersion',
        'subschemaSubentry',
        'objectClass',
        'rootDomainNamingContext',
      );

      $result = $this->search('', 'objectclass=*', 'base', $attributes);
      $this->rootdse = $result[''];
    }
  }

  /**
   * Returns the SubschemaSubentry for the server.
   *
   * @return string|NULL
   *  If available, the SubschemaSubentry string for the server, NULL if there is not one defined.
   */
  public function getSubschemaSubentry() {
    $this->setRootDse();
    return isset($this->rootdse['subschemasubentry'][0]) ? $this->rootdse['subschemasubentry'][0] : NULL;
  }

  public function connect() {
    try {
      $this->ldap->connect();
    }
    catch (SimpleLdapException $e) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * @return boolean
   *  TRUE if the server is readonly.
   */
  public function isReadOnly() {
    return $this->config->get('readonly');
  }
}
