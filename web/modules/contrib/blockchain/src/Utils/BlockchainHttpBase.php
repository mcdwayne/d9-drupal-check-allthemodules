<?php

namespace Drupal\blockchain\Utils;

/**
 * Class BlockchainHttpBase.
 *
 * @package Drupal\blockchain\Utils
 */
abstract class BlockchainHttpBase implements BlockchainHttpInterface {

  /**
   * Parameters.
   *
   * @var array
   */
  protected $params;

  /**
   * Ip address.
   *
   * @var string
   */
  protected $ip;

  /**
   * Port.
   *
   * @var string
   */
  protected $port;

  /**
   * Protocol security.
   *
   * @var bool
   */
  protected $secure;

  /**
   * {@inheritdoc}
   */
  public function getParam($key) {

    if (isset($this->params[$key])) {
      return $this->params[$key];
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setParam($key, $value) {

    $this->params[$key] = $value;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getParams() {
    return $this->params;
  }

  /**
   * {@inheritdoc}
   */
  public function hasParam($key) {
    return !($this->getParam($key) === NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function getIp() {
    return $this->ip;
  }

  /**
   * {@inheritdoc}
   */
  public function setIp($ip) {

    $this->ip = $ip;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPort() {
    return $this->port;
  }

  /**
   * {@inheritdoc}
   */
  public function setPort($port) {
    $this->port = $port;
    return $this;
  }

  /**
   * Defines if protocol.
   *
   * @return bool
   *   Test result.
   */
  public function isSecure() {

    return $this->secure;
  }

  /**
   * Setter for protocol security.
   *
   * @param bool $secure
   *   Value.
   *
   * @return $this
   *   Chaining.
   */
  public function setSecure($secure) {

    $this->secure = $secure;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEndPoint() {

    $protocol = $this->isSecure() ? 'https://' : 'http://';
    $port = $this->getPort() ? ':' . $this->getPort() : '';
    return $protocol . $this->getIp() . $port;
  }

  /**
   * {@inheritdoc}
   */
  public function setCountParam($value) {

    $this->setParam(static::PARAM_COUNT, $value);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCountParam() {

    return $this->getParam(static::PARAM_COUNT);
  }

  /**
   * {@inheritdoc}
   */
  public function hasCountParam() {

    return !($this->getCountParam() === NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function isCountParamValid() {

    return $this->hasCountParam() && $this->getCountParam() > 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getExistsParam() {
    return $this->getParam(static::PARAM_EXISTS);
  }

  /**
   * {@inheritdoc}
   */
  public function hasExistsParam() {
    return !($this->getExistsParam() === NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function setExistsParam($value) {

    $this->setParam(static::PARAM_EXISTS, $value);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBlocksParam() {

    return $this->getParam(static::PARAM_BLOCKS);
  }

  /**
   * {@inheritdoc}
   */
  public function hasBlocksParam() {

    return !($this->getBlocksParam() === NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function setBlocksParam(array $value) {

    $this->setParam(static::PARAM_BLOCKS, $value);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeParam() {

    return $this->getParam(static::PARAM_TYPE);
  }

  /**
   * {@inheritdoc}
   */
  public function hasTypeParam() {

    return !($this->getTypeParam() === NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function setTypeParam($value) {

    $this->setParam(static::PARAM_TYPE, $value);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasSelfParam() {

    return !($this->getSelfParam() === NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function getSelfParam() {

    return $this->getParam(static::PARAM_SELF);
  }

  /**
   * {@inheritdoc}
   */
  public function setSelfParam($value) {

    $this->setParam(static::PARAM_SELF, $value);

    return $this;
  }

}
