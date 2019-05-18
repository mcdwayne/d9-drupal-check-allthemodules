<?php

namespace Drupal\blockchain\Utils;

/**
 * Interface BlockchainHttpInterface.
 *
 * @package Drupal\blockchain\Utils
 */
interface BlockchainHttpInterface {

  const PARAM_EXISTS = 'exists';
  const PARAM_COUNT = 'count';
  const PARAM_BLOCKS = 'blocks';
  const PARAM_TYPE = 'type';
  const PARAM_SELF = 'self';

  /**
   * Getter for param if exists.
   *
   * @param string $key
   *   Name of param.
   *
   * @return string|array|null
   *   Value if any.
   */
  public function getParam($key);

  /**
   * Setter for property.
   *
   * @param string $key
   *   Name of param.
   * @param mixed $value
   *   Value of param.
   *
   * @return $this
   *   Chaining.
   */
  public function setParam($key, $value);

  /**
   * Getter for array of params.
   *
   * @return array
   *   Params.
   */
  public function getParams();

  /**
   * Predicate.
   *
   * @param string $key
   *   Name of key.
   *
   * @return bool
   *   Test result.
   */
  public function hasParam($key);

  /**
   * Getter for ip.
   *
   * @return string
   *   Value.
   */
  public function getIp();

  /**
   * Setter for property.
   *
   * @param string $ip
   *   Value.
   *
   * @return $this
   *   Chaining.
   */
  public function setIp($ip);

  /**
   * Getter for port.
   *
   * @return string
   *   Value.
   */
  public function getPort();

  /**
   * Setter for port.
   *
   * @param string $port
   *   Given port.
   *
   * @return $this
   *   Chaining.
   */
  public function setPort($port);

  /**
   * Defines if protocol.
   *
   * @return bool
   *   Test result.
   */
  public function isSecure();

  /**
   * Setter for protocol security.
   *
   * @param bool $secure
   *   Value.
   *
   * @return $this
   *   Chaining.
   */
  public function setSecure($secure);

  /**
   * Endpoint ready to be requested.
   *
   * @return string
   *   Endpoint.
   */
  public function getEndPoint();

  /**
   * Predicate.
   *
   * @return bool
   *   Test result.
   */
  public function hasCountParam();

  /**
   * Predicate.
   *
   * @return bool
   *   Test result.
   */
  public function isCountParamValid();

  /**
   * Getter for param.
   *
   * @return string
   *   Value.
   */
  public function getCountParam();

  /**
   * Setter for param.
   *
   * @param string $value
   *   Given value.
   *
   * @return $this
   *   Chaining.
   */
  public function setCountParam($value);

  /**
   * Getter for exists property.
   *
   * @return string
   *   Value.
   */
  public function getExistsParam();

  /**
   * Setter for exists property.
   *
   * @param string $value
   *   Given value.
   *
   * @return $this
   *   Chaining.
   */
  public function setExistsParam($value);

  /**
   * Predicate.
   *
   * @return bool
   *   Test result.
   */
  public function hasExistsParam();

  /**
   * Getter for exists property.
   *
   * @return array
   *   Value.
   */
  public function getBlocksParam();

  /**
   * Setter for exists property.
   *
   * @param array $value
   *   Given value.
   *
   * @return $this
   *   Chaining.
   */
  public function setBlocksParam(array $value);

  /**
   * Predicate.
   *
   * @return bool
   *   Test result.
   */
  public function hasBlocksParam();

  /**
   * Getter for exists property.
   *
   * @return string
   *   Value.
   */
  public function getTypeParam();

  /**
   * Setter for exists property.
   *
   * @param string $value
   *   Given value.
   *
   * @return $this
   *   Chaining.
   */
  public function setTypeParam($value);

  /**
   * Predicate.
   *
   * @return bool
   *   Test result.
   */
  public function hasTypeParam();

  /**
   * Predicate.
   *
   * @return bool
   *   Test result.
   */
  public function hasSelfParam();

  /**
   * Getter for param.
   *
   * @return string
   *   Value.
   */
  public function getSelfParam();

  /**
   * Setter for exists property.
   *
   * @param string $value
   *   Given value.
   *
   * @return $this
   *   Chaining.
   */
  public function setSelfParam($value);

}
