<?php

namespace Drupal\tag1quo\Adapter\Config;

/**
 * Class Config8.
 *
 * @internal This class is subject to change.
 */
class Config8 extends Config {

  /**
   * An ImmutableConfig object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * And editable Config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $editableConfig;

  /**
   * {@inheritdoc}
   */
  public function __construct($name) {
    parent::__construct($name);
    $this->config = \Drupal::configFactory()->get($name);
    $this->editableConfig = \Drupal::configFactory()->getEditable($name);
  }

  /**
   * {@inheritdoc}
   */
  public function clear($key) {
    $this->editableConfig->clear($key);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  protected function convertKey($key) {
    return $key;
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    $this->editableConfig->delete();
    return $this->save();
  }

  /**
   * {@inheritdoc}
   */
  public function get($key, $default = NULL) {
    $value = $this->config->get($key);
    return $value !== NULL ? $value : $default;
  }

  /**
   * {@inheritdoc}
   */
  public function set($key, $value) {
    $this->editableConfig->set($key, $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function save($has_trusted_data = FALSE) {
    $this->editableConfig->save($has_trusted_data);
    return $this;
  }

}
