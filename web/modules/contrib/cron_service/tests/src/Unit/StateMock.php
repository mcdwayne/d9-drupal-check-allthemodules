<?php

namespace Drupal\Tests\cron_service\Unit;

use Drupal\Core\State\StateInterface;

/**
 * Mock for state interface.
 *
 * @codeCoverageIgnore
 */
class StateMock implements StateInterface {

  /**
   * Data cache.
   *
   * @var array
   */
  public $data = [];

  /**
   * {@inheritdoc}
   */
  public function get($key, $default = NULL) {
    return isset($this->data[$key])
      ? $this->data[$key]
      : $default;
  }

  /**
   * {@inheritdoc}
   */
  public function getMultiple(array $keys) {
    return array_map(function ($key) {
      return $this->data[$key];
    }, $keys);
  }

  /**
   * {@inheritdoc}
   */
  public function set($key, $value) {
    $this->data[$key] = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function setMultiple(array $data) {
    $this->data = array_merge($this->data, $data);
  }

  /**
   * {@inheritdoc}
   */
  public function delete($key) {
    unset($this->data[$key]);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMultiple(array $keys) {
    foreach ($keys as $key) {
      unset($this->data[$key]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function resetCache() {
    // Do nothing here.
  }

}
