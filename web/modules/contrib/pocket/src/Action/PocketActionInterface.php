<?php

namespace Drupal\pocket\Action;

use Drupal\pocket\PocketItemInterface;

interface PocketActionInterface {

  /**
   * @param string $name
   *
   * @return mixed
   */
  public function get(string $name);

  /**
   * @param string $name
   * @param        $value
   *
   * @return $this
   */
  public function set(string $name, $value);

  /**
   * @param int $time
   *
   * @return mixed
   */
  public function setTime(int $time);

  /**
   * @return array
   */
  public function serialize(): array;

  /**
   * @return bool
   */
  public function isSuccessful(): bool;

  /**
   * @param bool $result
   *
   * @return mixed
   */
  public function setResult(bool $result);

  /**
   * @return \Drupal\pocket\PocketItemInterface|NULL
   */
  public function getResultItem();

  /**
   * @param \Drupal\pocket\PocketItemInterface $item
   *
   * @return mixed
   */
  public function setResultItem(PocketItemInterface $item);

}
