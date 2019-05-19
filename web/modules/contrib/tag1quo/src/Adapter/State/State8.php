<?php

namespace Drupal\tag1quo\Adapter\State;

/**
 * Class State8.
 *
 * @internal This class is subject to change.
 */
class State8 extends State {

  /**
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->state = \Drupal::state();
  }

  /**
   * {@inheritdoc}
   */
  public function delete($key) {
    $this->state->delete($key);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMultiple(array $keys) {
    $this->state->deleteMultiple($keys);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function get($key, $default = NULL) {
    return $this->state->get($key, $default);
  }

  /**
   * {@inheritdoc}
   */
  public function getMultiple(array $keys) {
    return $this->state->getMultiple($keys);
  }

  /**
   * {@inheritdoc}
   */
  public function set($key, $value) {
    $this->state->set($key, $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setMultiple(array $data) {
    $this->state->setMultiple($data);
    return $this;
  }

}
