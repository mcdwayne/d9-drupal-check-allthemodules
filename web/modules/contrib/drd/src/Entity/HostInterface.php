<?php

namespace Drupal\drd\Entity;

use Drupal\drd\EncryptionEntityInterface;

/**
 * Provides an interface for defining Host entities.
 *
 * @ingroup drd
 */
interface HostInterface extends BaseInterface, EncryptionEntityInterface {

  /**
   * Get language code of the host.
   *
   * @return string
   *   Language code.
   */
  public function getLangCode();

  /**
   * Get the remote Drush executable.
   *
   * @return string
   *   Drush executable.
   */
  public function getDrush();

  /**
   * Get the remote Drupal Console executable.
   *
   * @return string
   *   Drupal Console executable.
   */
  public function getDrupalConsole();

  /**
   * Get all cores hosted on this host.
   *
   * @param array $properties
   *   Extra properties for selection.
   *
   * @return \Drupal\drd\Entity\CoreInterface[]
   *   List of cores.
   */
  public function getCores(array $properties = []);

  /**
   * Get all domains hosted on this host.
   *
   * @param array $properties
   *   Extra properties for selection.
   *
   * @return \Drupal\drd\Entity\DomainInterface[]
   *   List of domains.
   */
  public function getDomains(array $properties = []);

  /**
   * Find out of this host is configured for SSH sessions.
   *
   * @return bool
   *   TRUE if SSH is configured.
   */
  public function supportsSsh();

  /**
   * Get SSH settings.
   *
   * @return array
   *   The SSH settings.
   */
  public function getSshSettings();

  /**
   * Set SSH settings.
   *
   * @param array $settings
   *   The SSH settings.
   *
   * @return $this
   */
  public function setSshSettings(array $settings);

  /**
   * Get the IP v4 address of the host.
   *
   * @param bool $refresh
   *   Whether to refresh status from remote.
   *
   * @return string
   *   The ip address.
   */
  public function getIpv4($refresh = TRUE);

  /**
   * Determine and store IP addresses of all associated domains.
   */
  public function updateIpAddresses();

  /**
   * Create new or return existing host entity DNS matching given name.
   *
   * @param string $name
   *   Hostname for DNS lookup to find existing host.
   *
   * @return $this
   */
  public static function findOrCreateByHost($name);

}
