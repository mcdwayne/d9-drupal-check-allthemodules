<?php

namespace Drupal\consent;

/**
 * The interface for consent information.
 */
class Consent implements ConsentInterface {

  /**
   * The raw array representation.
   *
   * @var array
   */
  protected $values;

  static protected $cid = 'cid';
  static protected $uid = 'uid';
  static protected $timestamp = 'timestamp';
  static protected $timezone = 'timezone';
  static protected $clientIp = 'client_ip';
  static protected $category = 'category';
  static protected $domain = 'domain';
  static protected $further = 'further';

  static protected $mandatory = ['uid', 'timestamp', 'timezone', 'client_ip', 'category', 'domain'];

  /**
   * Consent constructor.
   *
   * @param array $values
   *   An array of known raw values.
   */
  public function __construct(array $values = []) {
    $this->values = [
      static::$cid => NULL,
      static::$uid => NULL,
      static::$timestamp => NULL,
      static::$timezone => NULL,
      static::$clientIp => NULL,
      static::$category => NULL,
      static::$domain => NULL,
      static::$further => [],
    ];
    foreach ($values as $key => $value) {
      $this->set($key, $value);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isNew() {
    return (NULL === $this->getId());
  }

  /**
   * {@inheritdoc}
   */
  public function missingKeys() {
    $missing = [];
    $has_missing = FALSE;
    foreach (static::$mandatory as $key) {
      if (!isset($this->values[$key])) {
        $missing[] = $key;
        $has_missing = TRUE;
      }
    }
    return $has_missing ? $missing : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function get($key) {
    return isset($this->values[$key]) ? $this->values[$key] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function set($key, $value) {
    if (array_key_exists($key, $this->values)) {
      $this->values[$key] = $value;
    }
    else {
      $this->values[static::$further][$key] = $value;
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->values[static::$cid];
  }

  /**
   * {@inheritdoc}
   */
  public function setId($id) {
    $this->values[static::$cid] = $id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserId() {
    return $this->values[static::$uid];
  }

  /**
   * {@inheritdoc}
   */
  public function setUserId($uid) {
    $this->values[static::$uid] = $uid;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTimestamp() {
    return $this->values[static::$timestamp];
  }

  /**
   * {@inheritdoc}
   */
  public function setTimestamp($timestamp) {
    $this->values[static::$timestamp] = $timestamp;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTimezone() {
    return $this->values[static::$timezone];
  }

  /**
   * {@inheritdoc}
   */
  public function setTimezone($timezone) {
    $this->values[static::$timezone] = $timezone;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getClientIp() {
    return $this->values[static::$clientIp];
  }

  /**
   * {@inheritdoc}
   */
  public function setClientIp($client_ip) {
    $this->values[static::$clientIp] = $client_ip;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCategory() {
    return $this->values[static::$category];
  }

  /**
   * {@inheritdoc}
   */
  public function setCategory($category) {
    $this->values[static::$category] = $category;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDomain() {
    return $this->values[static::$domain];
  }

  /**
   * {@inheritdoc}
   */
  public function setDomain($domain) {
    $this->values[static::$domain] = $domain;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function rawArray() {
    $values = [];
    foreach ($this->values as $key => $value) {
      if (isset($value)) {
        $values[$key] = $value;
      }
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function storableValues() {
    $values = $this->rawArray();
    if (isset($values[static::$further])) {
      $further = $values[static::$further];
      if (!is_string($further)) {
        $values[static::$further] = json_encode($further, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_FORCE_OBJECT);
      }
    }
    return $values;
  }

}
