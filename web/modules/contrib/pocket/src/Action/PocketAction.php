<?php

namespace Drupal\pocket\Action;

use Drupal\pocket\PocketItemInterface;

abstract class PocketAction implements PocketActionInterface {

  /**
   * @var string
   */
  const ACTION = '';

  /**
   * @var array
   */
  private $values;

  /**
   * @var bool
   */
  private $result;

  /**
   * @var \Drupal\pocket\PocketItemInterface
   */
  private $item;

  /**
   * PocketAction constructor.
   *
   * @param array $values
   */
  public function __construct(array $values = []) {
    $this->values = $values;
  }

  /**
   * {@inheritdoc}
   */
  public function get(string $name) {
    return $this->values[$name] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function set(string $name, $value) {
    $this->values[$name] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function unset(string $name) {
    unset($this->values[$name]);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function serialize(): array {
    $this->values['action'] = $this->getName();
    return $this->values;
  }

  /**
   * {@inheritdoc}
   */
  public function setTime(int $time) {
    return $this->set('time', $time);
  }

  /**
   * {@inheritdoc}
   */
  public function isSuccessful(): bool {
    return $this->result;
  }

  /**
   * {@inheritdoc}
   */
  public function setResult(bool $result) {
    $this->result = $result;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getResultItem() {
    return $this->item;
  }

  /**
   * {@inheritdoc}
   */
  public function setResultItem(PocketItemInterface $item) {
    $this->item = $item;
  }

  /**
   * @return string
   */
  protected function getName(): string {
    return static::ACTION;
  }

}
