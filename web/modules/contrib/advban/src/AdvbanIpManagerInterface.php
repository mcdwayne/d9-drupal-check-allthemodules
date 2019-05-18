<?php

namespace Drupal\advban;

/**
 * Provides an interface defining a AdvbanIp manager.
 */
interface AdvbanIpManagerInterface {

  /**
   * Returns if this IP address is banned.
   *
   * @param string $ip
   *   The IP address to check.
   * @param array $options
   *   Options array.
   *
   * @return bool|array
   *   TRUE if the IP address is banned, FALSE otherwise
   *   or result info array.
   */
  public function isBanned($ip, array $options);

  /**
   * Finds all banned IP addresses.
   *
   * @return \Drupal\Core\Database\StatementInterface
   *   The result of the database query.
   */
  public function findAll();

  /**
   * Bans an IP address.
   *
   * @param string $ip
   *   The IP address to ban.
   * @param string $ip_end
   *   The end of the IP address to ban (optional).
   * @param int $expiry_date
   *   Expiry date of IP ban (optional).
   */
  public function banIp($ip, $ip_end, $expiry_date);

  /**
   * Unbans an IP address.
   *
   * @param string $ip
   *   The IP address to unban.
   * @param string $ip_end
   *   The end of the IP address to ban (optional).
   */
  public function unbanIp($ip, $ip_end);

  /**
   * Unbans all IP addresses.
   *
   * @param array $params
   *   Simple or/and range IP.
   *
   * @return int
   *   Deleted count.
   */
  public function unbanIpAll(array $params);

  /**
   * Finds a banned IP address by its ID.
   *
   * @param int $ban_id
   *   The ID for a banned IP address.
   *
   * @return string|false
   *   Either the banned IP address or FALSE if none exist with that ID.
   */
  public function findById($ban_id);

  /**
   * Format of the IP record (individual or range).
   *
   * @param string $ip
   *   Banned IP address.
   * @param string $ip_end
   *   Banned IP address (end of range).
   *
   * @return string
   *   Format string for IP addresses.
   */
  public function formatIp($ip, $ip_end);

  /**
   * Get expiry durations list or item.
   *
   * @param int $index
   *   Item index (optional).
   *
   * @return string|array
   *   List item or list.
   */
  public function expiryDurations($index);

  /**
   * Get default expiry duration index.
   *
   * @param array $expiry_durations
   *   Expiry durations array.
   * @param string $default_expiry_duration
   *   Default expiry duration.
   *
   * @return int
   *   Item index.
   */
  public function expiryDurationIndex(array $expiry_durations, $default_expiry_duration);

  /**
   * Unblock expired banned IP.
   */
  public function unblockExpiredIp();

  /**
   * Create formatted ban text.
   *
   * @param array $variables
   *   Variables array.
   *
   * @return string
   *   Formatted ban text.
   */
  public function banText(array $variables);

}
