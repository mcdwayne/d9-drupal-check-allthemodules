<?php

namespace Drupal\httpbl;

/**
 * Provides an interface defining the Httpbl manager.
 *
 * @ingroup httpbl_api
 */
interface HttpblManagerInterface {

  /**
   * Returns if this IP address is white-listed.
   *
   * @param string $ip
   *   The IP address to check.
   *
   * @return bool
   *   TRUE if the IP address is white-listed, FALSE otherwise.
   */
  public function isSafe($ip);

  /**
   * Returns if this IP address is blacklisted.
   *
   * @param string $ip
   *   The IP address to check.
   *
   * @return bool
   *   TRUE if the IP address is blacklisted, FALSE otherwise.
   */
  public function isBlacklisted($ip);

  /**
   * Returns if this IP address is greylisted.
   *
   * @param string $ip
   *   The IP address to check.
   *
   * @return bool
   *   TRUE if the IP address is greylisted, FALSE otherwise.
   */
  public function isGreylisted($ip);

  /**
   * White-lists an IP address.
   *
   * @param string $ip
   *   The IP address to white-list.
   */
  public function whitelistIp($ip);

 /**
   * Blacklists an IP address.
   *
   * @param string $ip
   *   The IP address to blacklist.
   */
  public function blacklistIp($ip);

 /**
   * Grey-lists an IP address.
   *
   * @param string $ip
   *   The IP address to grey-list.
   */
  public function greylistIp($ip);

}
