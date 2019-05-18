<?php

namespace Drupal\drupal_yext\YextContent;

/**
 * A class which will ignore everything done to it.
 *
 * Useful if we are running a migration but we don't want it to do anything.
 */
class YextIgnoreNode implements NodeMigrateDestinationInterface {

  /**
   * {@inheritdoc}
   */
  public function generate() {}

  /**
   * {@inheritdoc}
   */
  public function getYextLastUpdate() : int {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getYextRawDataString() : string {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getYextRawDataArray() : array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function setBio(string $bio) {}

  /**
   * {@inheritdoc}
   */
  public function setGeo(array $geo) {}

  /**
   * {@inheritdoc}
   */
  public function setCustom(string $id, string $value) {}

  /**
   * {@inheritdoc}
   */
  public function setHeadshot(string $url) {}

  /**
   * {@inheritdoc}
   */
  public function setName(string $name) {}

  /**
   * {@inheritdoc}
   */
  public function setYextId(string $id) {}

  /**
   * {@inheritdoc}
   */
  public function setYextLastUpdate(int $timestamp) {}

  /**
   * {@inheritdoc}
   */
  public function setYextRawData(string $data) {}

}
