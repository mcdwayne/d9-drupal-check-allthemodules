<?php

namespace Drupal\httpbl;

use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining an httpbl entity.
 *
 * @ingroup httpbl_api
 */
interface HostInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Returns the value for the host_ip field of a host entity.
   *
   * @return string
   *   The host IP address.
   */
  public function getHostIp();

  /**
   * Sets the value for the host_ip field of a host entity.
   *
   * @param string $ip
   *   The new Host Ip address.
   *
   * @return \Drupal\httpbl\HostInterface
   *   The called host entity.
   */
  public function setHostIp($ip);

  /**
   * Returns the value for the status field of a host entity.
   *
   * @return integer
   *   The host status (whether safe, grey or blacklisted).
   */
  public function getHostStatus();

  /**
   * Sets the value for the host_ip field of a host entity.
   *
   * @param integer $status
   *   The new Host status.
   *
   * @return \Drupal\httpbl\HostInterface
   *   The called host entity.
   */
  public function setHostStatus($status);

  /**
   * Returns the value for the status field of a host entity.
   *
   * @return integer (timestamp)
   *   The host expire field.
   */
  public function getExpiry();

  /**
   * Sets the value for the expire field of a host entity.
   *
   * @param integer $timestamp
   *   The new Host expire time.
   *
   * @return \Drupal\httpbl\HostInterface
   *   The called host entity.
   */
  public function setExpiry($timestamp);

  /**
   * Returns the value for the source field of a host entity.
   *
   * @return string
   *   The host source field.
   */
  public function getSource();

  /**
   * Sets the value for the source field of a host entity.
   *
   * @param string $source
   *   The new Host source.
   *
   * @return \Drupal\httpbl\HostInterface
   *   The called host entity.
   */
  public function setSource($source);

  /**
   * Creates a link to Project Honey Pot IP Address Inspector for a host entity.
   *
   * @param string $text
   *   The link text.
   *
   * @return string
   *   The formatted link.
   */
  public function projectLink($text = 'Project Honeypot');

  /**
   * Returns an array of host ids (hid).
   *
   * @param string $ip
   *   An IP address.
   *
   * @return array 
   *   An array of host entity ids with matching host_ip field.
   */
  public static function getHostsByIp($ip);

  /**
   * Returns an array of host objects.
   *
   * @param string $ip
   *   An IP address.
   *
   * @return array
   *   An array of host objects with matching host_ip field.
   */
  public static function loadHostsByIp($ip);

  /**
   * Returns a count of expired hosts.
   *
   * @param integer $now
   *   The value of \Drupal::time()->getRequestTime().
   *
   * @return integer
   *   A count of expired hosts.
   */
  public static function countExpiredHosts($now);

  /**
   * Returns an array of expired host ids (hid).
   *
   * @param integer $now
   *   The value of \Drupal::time()->getRequestTime().
   *
   * @return array 
   *   An array of host entity ids of expired hosts.
   */
  public static function getExpiredHosts($now);

  /**
   * Returns an array of expired host objects.
   *
   * @param integer $now
   *   The value of \Drupal::time()->getRequestTime().
   *
   * @return array
   *   An array of expired host objects.
   */
  public static function loadExpiredHosts($now);

}
