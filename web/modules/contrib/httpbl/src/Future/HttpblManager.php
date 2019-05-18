<?php

namespace Drupal\httpbl\Future;

/**
 * Httpbl Manager.
 *
 * No longer has any use.  Useful functions have been moved to other classes.
 *
 * @todo - Flesh out these functions or toss this class in the history bin.
 */
class HttpblManager implements HttpblManagerInterface {

  /**
   * {@inheritdoc}
   */
  public function isSafe($ip) {} // @todo flesh this out.  Could still use in Host entity, perhaps.

  /**
   * Returns if this IP address is blacklisted.
   *
   * @param string $ip
   *   The IP address to check.
   *
   * @return bool
   *   TRUE if the IP address is blacklisted, FALSE otherwise.
   */
  public function isBlacklisted($ip){} // @todo flesh this out.  Could still use in Host entity, perhaps.

  /**
   * Returns if this IP address is greylisted.
   *
   * @param string $ip
   *   The IP address to check.
   *
   * @return bool
   *   TRUE if the IP address is greylisted, FALSE otherwise.
   */
  public function isGreylisted($ip){} // @todo flesh this out.  Could still use in Host entity, perhaps.
  

 /**
   * White-lists an IP address.
   *
   * @param string $ip
   *   The IP address to white-list.
   */
  public function whitelistIp($ip){} // @todo flesh this out.  Could still use in Host entity, perhaps.

 /**
   * Blacklists an IP address.
   *
   * @param string $ip
   *   The IP address to blacklist.
   */
  public function blacklistIp($ip){} // @todo flesh this out.  Could still use in Host entity, perhaps.

 /**
   * Grey-lists an IP address.
   *
   * @param string $ip
   *   The IP address to grey-list.
   */
  public function greylistIp($ip){} // @todo flesh this out.  Could still use in Host entity, perhaps.

}
