<?php

namespace Drupal\consent;

/**
 * The interface for consent information.
 */
interface ConsentInterface {

  /**
   * Whether the consent information is new and not saved yet.
   *
   * @return bool
   *   Returns TRUE if new, FALSE otherwise.
   */
  public function isNew();

  /**
   * Returns a list of missing information keys.
   *
   * @return array|false
   *   Returns the array of keys or FALSE if the information is complete.
   */
  public function missingKeys();

  /**
   * Get the value of a certain key.
   *
   * @param string $key
   *   The key to get the value for.
   *
   * @return mixed
   *   The value or NULL if no value was found.
   */
  public function get($key);

  /**
   * Set the value of a certain key.
   *
   * @param string $key
   *   The key to set the value for.
   * @param mixed $value
   *   The value to set. Its data type must be scalar.
   *
   * @return $this
   *   The consent information itself.
   */
  public function set($key, $value);

  /**
   * Get the consent storage ID.
   *
   * @return int|null
   *   The consent storage ID or NULL if not given.
   */
  public function getId();

  /**
   * Set the consent storage ID.
   *
   * @param int $id
   *   The storage ID to set.
   *
   * @return $this
   *   The consent information itself.
   */
  public function setId($id);

  /**
   * Get the user ID.
   *
   * @return int|null
   *   The user ID or NULL if not given.
   */
  public function getUserId();

  /**
   * Set the user ID.
   *
   * @param int $uid
   *   The user ID.
   *
   * @return $this
   *   The consent information itself.
   */
  public function setUserId($uid);

  /**
   * Get the timestamp.
   *
   * @return int|null
   *   The timestamp or NULL if not given.
   */
  public function getTimestamp();

  /**
   * Set the timestamp.
   *
   * @param int $timestamp
   *   The timestamp.
   *
   * @return $this
   *   The consent information itself.
   */
  public function setTimestamp($timestamp);

  /**
   * Get the timezone.
   *
   * @return string|null
   *   The timezone or NULL if not given.
   */
  public function getTimezone();

  /**
   * Set the timezone.
   *
   * @param string $timezone
   *   The timezone to set.
   *
   * @return $this
   *   The consent information itself.
   */
  public function setTimezone($timezone);

  /**
   * Get the client IP address.
   *
   * @return string|null
   *   The IP address or NULL if not given.
   */
  public function getClientIp();

  /**
   * Set the client IP address.
   *
   * @param string $client_ip
   *   The client IP address to set.
   *
   * @return $this
   *   The consent information itself.
   */
  public function setClientIp($client_ip);

  /**
   * Get the category.
   *
   * @return string|null
   *   The category or NULL if not given.
   */
  public function getCategory();

  /**
   * Set the category.
   *
   * @param string $category
   *   The category as machine name.
   *
   * @return $this
   *   The consent information itself.
   */
  public function setCategory($category);

  /**
   * Get the domain where the user gave consent.
   *
   * @return string|null
   *   The domain or NULL if not given.
   */
  public function getDomain();

  /**
   * Set the domain where the user gave consent.
   *
   * @param string $domain
   *   The domain to set.
   *
   * @return $this
   *   The consent information itself.
   */
  public function setDomain($domain);

  /**
   * Get the raw array representation.
   *
   * @return array
   *   The array representation of the consent information.
   */
  public function rawArray();

  /**
   * Get storable data.
   *
   * @return array
   *   The storable data as key-value array.
   */
  public function storableValues();

}
