<?php

namespace Drupal\drd_pi;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\drd\EncryptionEntityInterface;

/**
 * Provides an interface for defining Account entities.
 */
interface DrdPiAccountInterface extends ConfigEntityInterface, EncryptionEntityInterface {

  /**
   * Module name for accounts of this type.
   *
   * @return string
   *   Module name.
   */
  public static function getModuleName();

  /**
   * Configuration name for accounts of this type.
   *
   * @return string
   *   Config name.
   */
  public static function getConfigName();

  /**
   * Name of the platform.
   *
   * @return string
   *   Platform name.
   */
  public function getPlatformName();

  /**
   * Synchronise this account with DRD.
   *
   * @return $this
   */
  public function sync();

  /**
   * Retrieve a list of hosts from the platform.
   *
   * @return DrdPiHost[]
   *   List of hosts.
   */
  public function getPlatformHosts();

  /**
   * Retrieve a list of cores for the host from the platform.
   *
   * @param DrdPiHost $host
   *   The host for which to retrieve cores.
   *
   * @return DrdPiCore[]
   *   List of cores.
   */
  public function getPlatformCores(DrdPiHost $host);

  /**
   * Retrieve a list of domains for host from the platform.
   *
   * @param DrdPiCore $core
   *   The core for which to retrieve domains.
   *
   * @return DrdPiDomain[]
   *   List of domains.
   */
  public function getPlatformDomains(DrdPiCore $core);

  /**
   * Retrieve the name of the automatic authorization method.
   *
   * @return string
   *   Name of the method.
   */
  public function getAuthorizationMethod();

  /**
   * Retrieve a list of secrets for automatic authorization.
   *
   * @param DrdPiDomain $domain
   *   The domain for which to retrieve the secrets.
   *
   * @return array
   *   List of secrets.
   */
  public function getAuthorizationSecrets(DrdPiDomain $domain);

}
