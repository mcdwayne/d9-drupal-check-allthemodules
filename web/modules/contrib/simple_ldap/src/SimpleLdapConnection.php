<?php

namespace Drupal\simple_ldap;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\simple_ldap\SimpleLdapException;
use Drupal\simple_ldap\SimpleLdapConnectionInterface;

class SimpleLdapConnection implements SimpleLdapConnectionInterface {
  /**
   * Simple LDAP Server Configuration
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * @var string
   */
  protected $connection_string;

  /**
   * The LDAP link identifier
   *
   * @var resource
   */
  private $connection;

  /**
   * Constructs a SimpleLdapConnection.
   *
   * @param ConfigFactoryInterface $config_factory
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory->get('simple_ldap.server');

    $connection_prefix = ($this->config->get('encryption') === 'ssl') ? 'ldaps://' : 'ldap://';

    $this->connection_string = $connection_prefix . $this->config->get('host') . ':' . $this->config->get('port');
  }

  public function __destruct() {
    $this->disconnect();
  }

  /**
   * Connect to the Simple LDAP server based on configuration settings.
   *
   * @throws \Drupal\simple_ldap\SimpleLdapException
   */
  public function connect() {
    if ($this->connection) {
      return;
    }

    $this->connection = @ldap_connect($this->connection_string);

    // Timeout after 10 seconds if connection is unsuccessful.
    @ldap_set_option($this->connection, LDAP_OPT_NETWORK_TIMEOUT, 10);

    if ($this->connection === FALSE) {
      throw new SimpleLdapException('Could not connect to LDAP server: ', $this->connection);
    }

    if ($this->config->get('encryption') === 'tls' && ldap_start_tls($this->connection) === FALSE) {
      throw new SimpleLdapException('Could not start TLS connection: ', $this->connection);
    }

    @ldap_set_option($this->connection, LDAP_OPT_PROTOCOL_VERSION, 3);
    @ldap_set_option($this->connection, LDAP_OPT_REFERRALS, (int) $this->config->get('opt_referrals'));
  }

  /**
   * Disconnect from LDAP server.
   */
  public function disconnect() {
    if ($this->connection && is_resource($this->connection)) {
      ldap_close($this->connection);
    }

    $this->connection = NULL;
  }

  /**
   * Get the LDAP link identifier for this SimpleLdapConnection
   *
   * @return resource
   */
  public function getResource() {
    return $this->connection;
  }
}
