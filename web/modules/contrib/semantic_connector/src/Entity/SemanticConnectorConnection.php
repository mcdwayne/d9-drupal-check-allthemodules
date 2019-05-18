<?php

/**
 * @file Contains \Drupal\semantic_connector\Entity\SemanticConnectorConnection
 */

namespace Drupal\semantic_connector\Entity;
use Drupal\Core\Config\Entity\ConfigEntityBase;

abstract class SemanticConnectorConnection extends ConfigEntityBase implements SemanticConnectorConnectionInterface {
  protected $id;
  protected $type;
  protected $url;
  protected $credentials;
  protected $title;
  protected $config;

  /**
   * {@inheritdoc|}
   */
  public function getId() {
    return $this->id;
  }

  /**
   * {@inheritdoc|}
   */
  public function getType() {
    return $this->type;
  }

  /**
   * {@inheritdoc|}
   */
  public function setType($type) {
    $this->type = $type;
  }

  /**
   * {@inheritdoc|}
   */
  public function getUrl() {
    return $this->url;
  }

  /**
   * {@inheritdoc|}
   */
  public function setUrl($url) {
    // Remove trailing slashes.
    $this->url = rtrim($url,"/");
  }

  /**
   * {@inheritdoc|}
   */
  public function getCredentials() {
    return $this->credentials;
  }

  /**
   * {@inheritdoc|}
   */
  public function setCredentials(array $credentials) {
    if (!isset($credentials['username']) || !isset($credentials['password'])) {
      // todo: throw an error.
    }
    $this->credentials = $credentials;
  }

  /**
   * {@inheritdoc|}
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * {@inheritdoc|}
   */
  public function setTitle($title) {
    $this->title = $title;
  }

  /**
   * {@inheritdoc|}
   */
  public function getConfig() {
    return $this->config;
  }

  /**
   * {@inheritdoc|}
   */
  public function setConfig(array $config) {
    $this->config = array_merge($this->getDefaultConfig(), $config);
  }
}
